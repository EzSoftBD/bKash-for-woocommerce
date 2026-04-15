<?php

namespace bKash\PGW;

use bKash\PGW\Models\Agreement;
use bKash\PGW\Models\Transaction;

class ProcessPayments {
	public $integration_type;
	public $allow_guest_checkout;
	private $bKashObj;


	public function __construct( $integration_type, $allow_guest_checkout = 'no' ) {
		$this->integration_type    = $integration_type;
		$this->allow_guest_checkout = $allow_guest_checkout;
		$this->bKashObj             = new ApiComm();
	}

	public function executePayment( string $orderPageURL, string $callbackURL = "" ) {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- external webhook/callback handler
		$message = "";

		// This endpoint is called by the payment gateway webhook, not a WP form.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- external callback, cannot use WP nonces
		$order_id    = sanitize_text_field( wp_unslash( $_REQUEST['orderId'] ?? '' ) );
		$payment_id  = sanitize_text_field( wp_unslash( $_REQUEST['paymentID'] ?? '' ) );
		$invoice_id  = sanitize_text_field( wp_unslash( $_REQUEST['invoiceID'] ?? '' ) );
		$status      = sanitize_text_field( wp_unslash( $_REQUEST['status'] ?? '' ) );
		$api_version = sanitize_text_field( wp_unslash( $_REQUEST['apiVersion'] ?? '' ) );

		// Log callback received
		$gateway = WC_Gateway_bKash::get_instance();
		if ( $gateway && $gateway->debug == 'yes' ) {
			$gateway->log->add( $gateway->id, 'Execute Payment Callback: Order #' . $order_id . ', Status: ' . $status . ', PaymentID: ' . $payment_id );
		}

		global $woocommerce;
		//To receive order id
		$order       = wc_get_order( $order_id );
		$trx         = new Transaction();
		$transaction = $trx->getTransaction( $invoice_id );

		// Ensure we have the payment mode from stored transaction
		$mode = $transaction ? $transaction->getMode() : null;

		if ( $status === 'success' ) {
			if ( $transaction && $transaction->getPaymentID() === $payment_id ) {

				$transaction->update( [
					'status' => 'CALLBACK_REACHED',
				] );

				// EXECUTE OPERATION
				$response = $this->bKashObj->executePayment( $transaction->getPaymentID() );

				if ( isset( $response['status_code'] ) && $response['status_code'] === 200 ) {
					if ( $gateway && $gateway->debug == 'yes' ) {
					$gateway->log->add( $gateway->id, 'Execute Payment API Success for Order #' . $order_id );
				}
					if ( $mode === '0000' ) {


						$agreementResp = Operations::processResponse( $response, "agreementID" );
						if ( is_array( $agreementResp ) ) {

							if ( $agreementResp['agreementStatus'] === 'Completed' ) {
								$agreementObj = new Agreement();
								$agreementObj->setAgreementID( $agreementResp['agreementID'] ?? '' );
								$agreementObj->setMobileNo( $agreementResp['customerMsisdn'] ?? '' );
								$agreementObj->setDateTime( $agreementResp['agreementExecuteTime'] ?? '' );
								$agreementObj->setUserID( $order->get_user_id() );
								$stored = $agreementObj->save();

								if ( $stored ) {

									$transaction->update( [ 'mode' => '0001' ], [ 'payment_id' => $transaction->getPaymentID() ] );
								// HPOS-compatible: store bkmode via order meta API
								$order->update_meta_data( '_bkmode', '0001' );
								$order->save();
									$createResp = $this->createPayment( $transaction->getOrderID(), $transaction->getIntent(), $callbackURL );

									if ( isset( $createResp['redirect'] ) ) {
										wp_safe_redirect( esc_url_raw( $createResp['redirect'] ) );
										exit;
									}

									echo json_encode( $createResp );
								} else {
									$message = "Agreement cannot be done right now, cannot store in db, try again. " . $agreementObj->errorMessage;
									$message = $this->processResponse( $message );
								}

							} else {
								$message = $this->processResponse( "Agreement cannot be done right now, try again" );
							}

						} else {
							$message = is_string( $agreementResp ) ? $agreementResp : '';
							$message = $this->processResponse( $message );
						}

					} else {
						// GET TRXID FROM BKASH RESPONSE
						$paymentResp = Operations::processResponse( $response, "trxID" );

						if ( is_array( $paymentResp ) ) {

							// PAYMENT IS DONE SUCCESSFULLY, NOW START REST OF THE PROCESS TO UPDATE WC ORDER

							// Updating transaction status
							$updated = $transaction->update( [
								'status' => $paymentResp['transactionStatus'] ?? 'NO_STATUS_EXECUTE',
								'trx_id' => $paymentResp['trxID'] ?? ''
							] );

							if ( $updated && isset( $paymentResp['trxID'] ) && ! empty( $paymentResp['trxID'] ) ) {

								// Payment complete.
								if ( ( $paymentResp['transactionStatus'] ?? null ) === 'Authorized' ) {
									$order->update_status( 'on-hold' );
								} elseif ( ( $paymentResp['transactionStatus'] ?? null ) === 'Completed' ) {
									$order->payment_complete();
								} else {
									$order->update_status( 'pending' );
								}

// Store the transaction ID – use the order API for HPOS compatibility.
									$order->set_transaction_id( $paymentResp['trxID'] );
									$order->save();

								// Add order note and log via gateway logger when available.
								$order->add_order_note( sprintf( 'bKash PGW payment approved (ID: %s)', $paymentResp['trxID'] ) );

								if ( $gateway && isset( $gateway->log ) ) {
									$gateway->log->add( $gateway->id, 'bKash PGW payment approved (ID: ' . ( $paymentResp['trxID'] ?? '' ) . ')' );
								}

								// Reduce stock levels.
								wc_reduce_stock_levels( $order_id );

							// Empty cart only after confirmed successful payment execution.
							if ( WC()->cart ) {
								WC()->cart->empty_cart();
							}

							if ( $gateway && isset( $gateway->log ) ) {
								$gateway->log->add( $gateway->id, 'Stock reduced and cart emptied for Order #' . $order_id );
								}


								// Return thank you page redirect.
								if ( $this->integration_type === 'checkout' ) {
									echo json_encode( array(
										'result'   => 'success',
										'redirect' => $orderPageURL
									) );
									die();
								}
								wp_safe_redirect( esc_url_raw( $orderPageURL ) );
								exit;
							}

							if ( $updated && isset( $paymentResp['paymentID'] ) && ! empty( $paymentResp['paymentID'] ) ) {
								$msg = "Transaction was not successful, last transaction status: "
								       . $paymentResp['transactionStatus'] ?? 'NO_STATUS_EXECUTE';
								if ( $this->integration_type === 'checkout' ) {
									echo json_encode( array(
										'result'  => 'failure',
										'message' => $msg
									) );
									die();
								} else {
									$this->redirectToFailurePage( $msg, $order_id );
								}
							}
							$message = "Could not get transaction status";
						} else {
							$message = is_string( $paymentResp ) ? $paymentResp : '';
						}

						$transaction->update( [
							'status' => 'Failed',
						] );
						$order->add_order_note( "bKash Payment: " . $message );
					if ( $gateway && $gateway->debug == 'yes' ) {
						$gateway->log->add( $gateway->id, 'Payment Execution Failed for Order #' . $order_id . '. Message: ' . $message );
					}
					}
				} else {
					$message = $this->processResponse( "Communication issue with payment gateway" );
				}
				if ( $this->integration_type === 'checkout' ) {
					echo json_encode( array(
						'result'  => 'failure',
						'message' => $message
					) );
				} else {
					$this->redirectToFailurePage( $message, $order_id );
				}

				die();
			}
			// payment ID not matching or transaction not found. or already processed
			$message = $this->processResponse( "Invalid payment ID or Invoice ID" );

		} else {
			// transaction failed/cancelled.
			$status = str_replace( [ 'cancel', 'failure' ], [ 'Cancelled', 'Failed' ], $status );
			$wc_status     = ( $status === 'Cancelled' ) ? 'cancelled' : 'failed';
			$plain_message = 'bKash payment ' . strtolower( $status ) . '. Please try again.';

			if ( $transaction && $transaction->getStatus() !== 'Completed' ) {
				$transaction->update( [
					'status' => esc_html( $status ),
				] );
				// Update WooCommerce order status to reflect the payment outcome
				$order->update_status( $wc_status, 'bKash Payment ' . $status . '.' );
				if ( $gateway && $gateway->debug == 'yes' ) {
					$gateway->log->add( $gateway->id, 'Callback received with non-success status for Order #' . $order_id . '. Status: ' . esc_html( $status ) );
				}
			} elseif ( $transaction ) {
				$order->add_order_note( "bKash Payment is already in Completed state. Tried to change Status to => " . esc_html( $status ) );
				if ( $gateway && $gateway->debug == 'yes' ) {
					$gateway->log->add( $gateway->id, 'Callback Status Change Rejected for Order #' . $order_id . ' - Already Completed' );
				}
			}

			$message = $this->processResponse( "Transaction is " . $status );
		}

		if ( $this->integration_type === 'checkout' ) {
			echo json_encode( array(
				'result'  => 'failure',
				'message' => $message
			) );
			die();
		} else {
			$notice_text    = isset( $plain_message ) ? $plain_message : wp_strip_all_tags( $message );
			$redirect_status = isset( $wc_status ) && $wc_status === 'cancelled' ? 'cancel' : 'failure';
			$this->redirectToFailurePage( $notice_text, $order_id, $redirect_status );
		}

		// Return message to customer.
		die();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * @param string $order_id
	 * @param string $intent
	 * @param string $callbackURL
	 *
	 * @return array|null
	 * */
	public function createPayment( string $order_id, string $intent = 'sale', string $callbackURL = "" ) {
		global $woocommerce;
		$message      = '';
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Payment data passed from WooCommerce checkout gateway callback; nonce is verified in the gateway init.
		$isAgreement  = isset( $_REQUEST['agreement'] );
		$agreement_id = isset( $_REQUEST['agreement_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['agreement_id'] ) ) : null;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Log payment creation start
		$gateway = WC_Gateway_bKash::get_instance();
		if ( $gateway && $gateway->debug == 'yes' ) {
			$gateway->log->add( $gateway->id, 'Creating Payment for Order #' . $order_id . '. Integration Type: ' . $this->integration_type . ', Intent: ' . $intent );
		}

		//To receive order id and total
		$order    = wc_get_order( $order_id );
		$amount   = number_format( (float) $order->get_total(), 2, '.', '' );
		$currency = get_woocommerce_currency();

		//To receive user id and order details
		$isLoggedIn         = is_user_logged_in();
		$merchantCustomerId = $order->get_user_id();
		$merchantOrderId    = $order->get_order_number();
		$billingPhone       = $order->get_billing_phone();

		if ( $this->integration_type === 'checkout' ) {
			$payment_payload = array(
				'amount'                => $amount,
				'currency'              => $currency,
				'intent'                => $intent,
				'merchantInvoiceNumber' => uniqid( "bfw_", false ) . '_' . $merchantOrderId,
			);
		} else {

			// Check if already has agreement
			$storedAgreementID = "";
			$mode              = null;

			// Check if user is logged in
			if ( ! empty( $order->get_user_id() ) ) {

				if ( $agreement_id === 'new' || $agreement_id === 'no' ) {
					// If customer wants to add new number then mode 0000, or without agreement 0011
					$mode = $agreement_id === 'new' ? '0000' : '0011';
				} else if ( $agreement_id ) {
					// Customer selected an agreement to pay
					$storedAgreementID = $agreement_id;
				} else {
					// Proceed with stored latest agreement id
					$agreementObj = new Agreement();
					$agreement    = $agreementObj->getAgreement( "", $order->get_user_id() );
					if ( $agreement ) {
						$storedAgreementID = $agreement->getAgreementID();
					}
				}
			} else {
				// Non-logged in user
				if ( $this->integration_type === 'tokenized' ) {
				if ( $this->allow_guest_checkout !== 'yes' ) {
					wc_add_notice( "Please login to proceed with tokenized payment", "error" );

					return [ 'result' => 'failure' ];
				}
				// Guest checkout allowed — pay without agreement
				$mode = '0011';
				}
			}


			if ( ! $mode ) {
				$mode = Operations::getTokenizedPaymentMode( $this->integration_type, $order_id, $isAgreement, $storedAgreementID );
			}

			// Use billing phone as payerReference — bKash expects a meaningful payer identifier.
			// Fall back to a unique ID only if no phone is available.
			$payerReference = ! empty( $billingPhone )
				? $billingPhone
				: ( uniqid( 'bKash_', false ) . '_' . $merchantCustomerId );

			$payment_payload = array(
				'mode'                  => $mode,
				'payerReference'        => $payerReference,
				'callbackURL'           => $callbackURL,
				'agreementID'           => $storedAgreementID ?? '',
				'amount'                => $amount,
				'currency'              => $currency,
				'intent'                => $intent,
				'merchantInvoiceNumber' => uniqid( "bfw_", false ) . '_' . $merchantOrderId
			);
		}

		/* Store Transaction in Database */
		$trx = new Transaction();
		$trx->setOrderID( $order_id );
		$trx->setAmount( $amount );
		$trx->setIntegrationType( $this->integration_type );
		$trx->setIntent( $intent );
		$trx->setCurrency( $currency );
		$trx->setMode( $mode ?? '' );
		$trx->setStatus( "Created" );

		if ( isset( $payment_payload['merchantInvoiceNumber'] ) ) {
			$trx->setInvoiceID( $payment_payload['merchantInvoiceNumber'] );
		}

		$trxSaved = $trx->save();

		if ( $trxSaved ) {
			// pass invoice number in callback string
			if ( isset( $payment_payload['callbackURL'] ) ) {
				$payment_payload['callbackURL'] .= '&invoiceID=' . $trxSaved->getInvoiceID();
			}

			if ( $gateway && $gateway->debug == 'yes' ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Debug logging only runs when plugin debug mode is enabled.
				$gateway->log->add( $gateway->id, 'Payment Create API Call for Order #' . $order_id . '. Payload: ' . wp_json_encode( $payment_payload ) );
			}

			$createResponse = $this->bKashObj->paymentCreate( $payment_payload );

			if ( isset( $createResponse['status_code'] ) && $createResponse['status_code'] === 200 ) {
				$response = isset( $createResponse['response'] ) && is_string( $createResponse['response'] ) ? json_decode( $createResponse['response'], true ) : [];

				if ( $response ) {
					// Detect bKash API-level errors (statusCode is present in both checkout and tokenized error responses)
					$api_status_code = $response['statusCode'] ?? $response['errorCode'] ?? null;
					$api_status_msg  = $response['statusMessage'] ?? $response['errorMessage'] ?? '';

					$has_api_error = false;

					// Tokenized error: statusMessage present and not 'Successful'
					if ( isset( $response['statusMessage'] ) && $response['statusMessage'] !== 'Successful' ) {
						$has_api_error = true;
					}
					// Checkout error: errorCode present
					if ( isset( $response['errorCode'] ) ) {
						$has_api_error = true;
					}

					if ( $has_api_error ) {
						$message = $api_status_msg;

						if ( $gateway && $gateway->debug == 'yes' ) {
							$gateway->log->add( $gateway->id, 'Payment Create API Error for Order #' . $order_id . '. Code: ' . $api_status_code . ', Message: ' . $message );
						}

						// Mark transaction failed so order status updates properly
						$trxSaved->update( [ 'status' => 'Failed' ] );
						$order = wc_get_order( $order_id );
						if ( $order && $order->get_status() === 'pending' ) {
							$order->update_status( 'failed', 'bKash Payment Create Error: ' . $message );
						}

						wc_add_notice( $message, 'error' );

						return [
							'result'    => 'failure',
							'message'   => $message,
							'errorCode' => (string) $api_status_code,
						];
					} else if ( isset( $response['paymentID'] ) && ! empty( $response['paymentID'] ) ) {

						if ( $gateway && $gateway->debug == 'yes' ) {
							$gateway->log->add( $gateway->id, 'Payment Created Successfully for Order #' . $order_id . '. PaymentID: ' . $response['paymentID'] );
						}

						$updated = $trxSaved->update( [ 'payment_id' => $response['paymentID'] ] );
						if ( $updated ) {

							if ( $this->integration_type === 'checkout' ) {
								return array(
									'result'   => 'success',
									'redirect' => null,
									'order'    => array(
										'orderId'   => $order_id,
										'paymentID' => $response['paymentID'] ?? '',
										'invoiceID' => $trx->getInvoiceID(),
										'amount'    => $amount,
									),
									'response' => $response
								);
							} else {
								return array(
									'result'   => 'success',
									'redirect' => $response['bkashURL']
								);
							}
						} else {
							$message = $this->processResponse( "Cannot process this payment right now, payment ID issue" );
							if ( $gateway && $gateway->debug == 'yes' ) {
								$gateway->log->add( $gateway->id, 'Payment ID Update Failed for Order #' . $order_id );
							}
						}
					} else {
						$message = $this->processResponse( "Cannot process this payment right now, unknown error message" );
						if ( $gateway && $gateway->debug == 'yes' ) {
							$gateway->log->add( $gateway->id, 'Payment Create API Unknown Error for Order #' . $order_id );
						}
					}
				} else {
					$message = $this->processResponse( "Cannot process this payment right now, not a valid response" );
					if ( $gateway && $gateway->debug == 'yes' ) {
						$gateway->log->add( $gateway->id, 'Invalid API Response for Order #' . $order_id );
					}
				}
			} else {
				$message = "Cannot process this payment right now, error in communication";
				if ( $gateway && $gateway->debug == 'yes' ) {
					$gateway->log->add( $gateway->id, 'API Communication Error for Order #' . $order_id . '. Status Code: ' . ( $createResponse['status_code'] ?? 'N/A' ) );
				}
				// Mark order failed for HTTP-level errors
				$order = wc_get_order( $order_id );
				if ( $order && $order->get_status() === 'pending' ) {
					$order->update_status( 'failed', 'bKash: ' . $message );
				}
			}
		} else {
			$message = $trx->errorMessage;
			if ( $gateway && $gateway->debug == 'yes' ) {
				$gateway->log->add( $gateway->id, 'Transaction Save Error for Order #' . $order_id . '. Error: ' . $message );
			}
		}

		wc_add_notice( $message, 'error' );

		return [
			'result'    => 'failure',
			'message'   => $message,
			'errorCode' => '',
		];
	}

	public function processResponse( $message, $type = 'error' ) {
		return $message;
	}

	/**
	 * Redirect the browser to the bKash payment failure page, passing error details as URL params.
	 * Works with both classic checkout and Block Checkout (which ignores wc_add_notice).
	 *
	 * @param string $message  Plain-text error message to display.
	 * @param string $order_id WC order ID (optional).
	 * @param string $status   'failure' or 'cancel'.
	 */
	private function redirectToFailurePage( string $message, string $order_id = '', string $status = 'failure' ): void {
		$params = [ 'status' => $status, 'message' => $message ];
		if ( ! empty( $order_id ) ) {
			$params['orderId'] = $order_id;
		}
		wp_safe_redirect( esc_url_raw( get_site_url() . '/wc-api/bkash_payment_failure?' . http_build_query( $params ) ) );
		exit;
		die();
	}


	public function cancelPayment( string $order_id ) {

		global $woocommerce;
		$gateway = WC_Gateway_bKash::get_instance();
		if ( $gateway && $gateway->debug == 'yes' ) {
			$gateway->log->add( $gateway->id, 'Cancel Payment Request for Order #' . $order_id );
		}

		//To receive order id
		$order = wc_get_order( $order_id );
		if ( $order ) {

			if ( $order->get_status() === 'pending' ) {

				$trx         = new Transaction();
				$transaction = $trx->getTransactionByOrderId( $order_id );
				if ( $transaction ) {

					$transaction->update( [
						'status' => 'Cancelled',
					] );
					$order->add_order_note( "bKash Payment has been cancelled, either failed or customer cancelled" );
					$order->update_status( 'cancelled', 'Payment has been cancelled!' );

					if ( $gateway && $gateway->debug == 'yes' ) {
						$gateway->log->add( $gateway->id, 'Payment Cancelled Successfully for Order #' . $order_id );
					}

					return array(
						'result'   => 'success',
						'redirect' => null,
						'response' => "Order cancelled!"
					);

				}

				if ( $gateway && $gateway->debug == 'yes' ) {
					$gateway->log->add( $gateway->id, 'Cancel Payment Failed for Order #' . $order_id . ' - Transaction not found' );
				}

				return array(
					'result'  => 'failure',
					'message' => 'Transaction not found in bKash database'
				);

			}

			if ( $gateway && $gateway->debug == 'yes' ) {
				$gateway->log->add( $gateway->id, 'Cancel Payment Failed for Order #' . $order_id . ' - Order not in pending status' );
			}

			return array(
				'result'  => 'failure',
				'message' => 'Order is not in pending status to cancel the payment'
			);
		}

		if ( $gateway && $gateway->debug == 'yes' ) {
			$gateway->log->add( $gateway->id, 'Cancel Payment Failed for Order #' . $order_id . ' - Order not found' );
		}

		return array(
			'result'  => 'failure',
			'message' => 'Order not found'
		);

	}


}