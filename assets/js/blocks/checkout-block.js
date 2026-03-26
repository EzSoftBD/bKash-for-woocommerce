/**
 * bKash Payment Gateway - WooCommerce Blocks Integration
 *
 * Registers bKash as a payment method for the block-based WooCommerce checkout.
 * Requires no build step - uses WC/WP globals that Blocks already exposes.
 *
 * @since 1.1.0
 */
( function () {
    'use strict';

    /* -----------------------------------------------------------------------
     * 1. Guard - wait until the Blocks registry is bootstrapped.
     * -------------------------------------------------------------------- */
    if (
        ! window.wc ||
        ! window.wc.wcBlocksRegistry ||
        ! window.wc.wcSettings ||
        ! window.wp ||
        ! window.wp.element
    ) {
        return;
    }

    var wcBlocksRegistry = window.wc.wcBlocksRegistry;
    var wcSettings       = window.wc.wcSettings;
    var wpElement        = window.wp.element;
    var wpI18n           = window.wp.i18n           || {};
    var wpHtmlEntities   = window.wp.htmlEntities   || {};

    var registerPaymentMethod = wcBlocksRegistry.registerPaymentMethod;
    var getSetting            = wcSettings.getSetting;
    var createElement         = wpElement.createElement;
    var useState              = wpElement.useState;
    var useEffect             = wpElement.useEffect;
    var useRef                = wpElement.useRef;
    var Fragment              = wpElement.Fragment;
    var decodeEntities = wpHtmlEntities.decodeEntities || function ( s ) { return s; };
    var __             = wpI18n.__                     || function ( s ) { return s; };

    /* -----------------------------------------------------------------------
     * 2. Settings injected server-side via BkashBlockPaymentMethod::get_payment_method_data()
     * -------------------------------------------------------------------- */
    var settings = getSetting( 'bkash-for-woocommerce_data', {} );

    var SLUG             = settings.bKash_slug          || 'bkash-for-woocommerce';
    var TITLE            = decodeEntities( settings.title || 'bKash Payment Gateway' );
    var DESCRIPTION      = decodeEntities( settings.description || '' );
    var ICON_URL         = settings.icon                || '';
    var INTEGRATION_TYPE = settings.integration_type   || 'checkout';
    var AGREEMENTS       = settings.agreements          || [];
    var IS_LOGGED_IN     = !! settings.is_logged_in;
    var ALLOW_GUEST      = settings.allow_guest_checkout === 'yes';
    var BKASH_SCRIPT_URL = settings.bKashScriptURL      || '';
    var EXECUTE_URL      = settings.wcAjaxURL           || '';
    var CANCEL_URL       = settings.wcPaymentCancelUrl  || '';
    var CANCEL_AGREE_URL = settings.cancelAgreement     || '';
    var SUBMIT_ORDER_URL = settings.submit_order        || '';

    var FEATURES = ( Array.isArray( settings.supports ) && settings.supports.length > 0 )
        ? settings.supports
        : [ 'products' ];

    /* -----------------------------------------------------------------------
     * 3. bKash SDK - lazy-loaded once, callback-based.
     * -------------------------------------------------------------------- */
    var bKashSDKLoaded  = false;
    var bKashSDKLoading = false;
    var sdkCallbacks    = [];

    function loadBkashSDK( callback ) {
        if ( bKashSDKLoaded ) { callback(); return; }
        sdkCallbacks.push( callback );
        if ( bKashSDKLoading ) return;
        bKashSDKLoading = true;
        var script    = document.createElement( 'script' );
        script.src    = BKASH_SCRIPT_URL;
        script.onload = function () {
            bKashSDKLoaded  = true;
            bKashSDKLoading = false;
            sdkCallbacks.forEach( function ( cb ) { cb(); } );
            sdkCallbacks = [];
        };
        script.onerror = function () {
            bKashSDKLoading = false;
            sdkCallbacks    = [];
            console.error( '[bKash Blocks] Failed to load SDK:', BKASH_SCRIPT_URL );
        };
        document.head.appendChild( script );
    }

    /* -----------------------------------------------------------------------
     * 4. Helper - ensure hidden bKash SDK button exists in DOM.
     * -------------------------------------------------------------------- */
    function ensureBkashButton() {
        var btn = document.getElementById( 'bKash_button' );
        if ( ! btn ) {
            btn           = document.createElement( 'button' );
            btn.id        = 'bKash_button';
            btn.type      = 'button';
            btn.style.cssText = 'display:none!important;visibility:hidden;position:absolute;';
            document.body.appendChild( btn );
        }
        return btn;
    }

    /* -----------------------------------------------------------------------
     * 5. Label component
     * -------------------------------------------------------------------- */
    function PaymentLabel() {
        return createElement(
            Fragment, null,
            ICON_URL
                ? createElement( 'img', {
                      src: ICON_URL, alt: TITLE,
                      style: { height: '24px', marginRight: '8px', verticalAlign: 'middle' },
                  } )
                : null,
            createElement( 'span', null, TITLE )
        );
    }

    /* -----------------------------------------------------------------------
     * 6. Tokenized content - saved bKash agreements list
     * -------------------------------------------------------------------- */
    function TokenizedContent( props ) {
        var eventRegistration = props.eventRegistration;
        var emitResponse      = props.emitResponse;

        var stateArr          = useState( AGREEMENTS.length > 0 ? AGREEMENTS[0].agreement_token : 'new' );
        var selectedAgreement = stateArr[0];
        var setAgreement      = stateArr[1];

        useEffect( function () {
            var unsubscribe = eventRegistration.onPaymentSetup( async function () {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: { paymentMethodData: { agreement_id: selectedAgreement } },
                };
            } );
            return unsubscribe;
        }, [ selectedAgreement ] );

        function handleCancel( token ) {
            if ( ! token ) return;
            fetch( CANCEL_AGREE_URL + '?id=' + encodeURIComponent( token ) )
                .then( function ( r ) { return r.json(); } )
                .then( function ( data ) {
                    if ( data.result === 'success' ) {
                        window.location.reload();
                    } else {
                        alert( data.message || __( 'Could not remove agreement.', 'bkash-for-woocommerce' ) );
                    }
                } )
                .catch( function () {
                    alert( __( 'Network error. Please try again.', 'bkash-for-woocommerce' ) );
                } );
        }

        var agreementRows = AGREEMENTS.map( function ( agreement ) {
            return createElement(
                'tr', { key: agreement.agreement_token },
                createElement( 'td', null,
                    createElement( 'label', null,
                        createElement( 'input', {
                            type: 'radio', name: 'agreement_id',
                            value: agreement.agreement_token,
                            checked: selectedAgreement === agreement.agreement_token,
                            onChange: function () { setAgreement( agreement.agreement_token ); },
                        } ),
                        ' ', agreement.phone
                    )
                ),
                createElement( 'td', null,
                    createElement( 'a', {
                        href: '#',
                        style: { color: 'red', fontSize: '12px' },
                        onClick: function ( e ) { e.preventDefault(); handleCancel( agreement.agreement_token ); },
                    }, __( 'Remove', 'bkash-for-woocommerce' ) )
                )
            );
        } );

        var newRow = createElement(
            'tr', null,
            createElement( 'td', { colSpan: 2 },
                createElement( 'label', null,
                    createElement( 'input', {
                        type: 'radio', name: 'agreement_id', value: 'new',
                        checked: selectedAgreement === 'new',
                        onChange: function () { setAgreement( 'new' ); },
                    } ),
                    ' ', __( 'Pay and remember a new bKash account', 'bkash-for-woocommerce' )
                )
            )
        );

        var tableChildren = agreementRows.concat( [ newRow ] );

        return createElement(
            'div', { className: 'wc-block-bkash-tokenized' },
            DESCRIPTION ? createElement( 'p', null, DESCRIPTION ) : null,
            AGREEMENTS.length > 0
                ? createElement.apply( null, [ 'table', { id: 'payment-fields-table', style: { width: '100%' } } ].concat( tableChildren ) )
                : createElement( 'p', null, __( 'A new bKash agreement will be created.', 'bkash-for-woocommerce' ) ),
            ! IS_LOGGED_IN && ! ALLOW_GUEST
                ? createElement( 'p', { style: { color: 'red' } }, __( 'Please log in to complete the payment.', 'bkash-for-woocommerce' ) )
                : null
        );
    }

    /* -----------------------------------------------------------------------
     * 7. Checkout content - triggers the bKash SDK modal on order placement.
     * -------------------------------------------------------------------- */
    function CheckoutContent( props ) {
        var eventRegistration = props.eventRegistration;
        var emitResponse      = props.emitResponse;
        var stateRef          = useRef( { paymentID: '', orderId: '', invoiceID: '' } );

        useEffect( function () {
            if ( ! BKASH_SCRIPT_URL ) return;
            loadBkashSDK( function () {
                ensureBkashButton();
                if ( typeof window.bKash === 'undefined' ) {
                    console.error( '[bKash Blocks] bKash SDK object missing.' );
                    return;
                }
                var paymentReq = {
                    amount: '0',
                    intent: settings.intent || 'sale',
                    paymentURL: SUBMIT_ORDER_URL,
                    body: {},
                };
                window.bKash.init( {
                    paymentMode: 'checkout',
                    paymentRequest: paymentReq,
                    createRequest: function () {
                        var formData = new URLSearchParams();
                        Object.keys( paymentReq.body || {} ).forEach( function ( k ) {
                            formData.append( k, paymentReq.body[ k ] );
                        } );
                        formData.append( 'action', 'ajax_order' );
                        fetch( paymentReq.paymentURL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: formData.toString(),
                        } )
                            .then( function ( r ) { return r.json(); } )
                            .then( function ( result ) {
                                if ( result.result === 'success' ) {
                                    stateRef.current.paymentID  = result.order.paymentID  || '';
                                    stateRef.current.orderId    = result.order.orderId    || '';
                                    stateRef.current.invoiceID  = result.order.invoiceID  || '';
                                    window.bKash.create().onSuccess( result.response );
                                } else {
                                    window.bKash.execute().onError();
                                }
                            } )
                            .catch( function () { window.bKash.execute().onError(); } );
                    },
                    executeRequestOnAuthorization: function () {
                        var s    = stateRef.current;
                        var body = new URLSearchParams( {
                            action:     'bk_execute',
                            security:   '',
                            orderId:    s.orderId,
                            paymentID:  s.paymentID,
                            invoiceID:  s.invoiceID,
                            status:     'success',
                            apiVersion: settings.api_version || 'v1.2.0-beta',
                        } );
                        fetch( EXECUTE_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: body.toString(),
                        } )
                            .then( function ( r ) { return r.json(); } )
                            .then( function ( resp ) {
                                if ( resp.result === 'success' && resp.redirect ) {
                                    window.location.href = resp.redirect;
                                } else {
                                    window.bKash.execute().onError();
                                }
                            } )
                            .catch( function () { window.bKash.execute().onError(); } );
                    },
                    onClose: function () {
                        window.bKash.execute().onError();
                        var s    = stateRef.current;
                        var body = new URLSearchParams( {
                            action: 'bk_cancel', security: '',
                            orderId: s.orderId, paymentID: s.paymentID,
                            invoiceID: s.invoiceID, status: 'cancel',
                            apiVersion: settings.api_version || 'v1.2.0-beta',
                        } );
                        fetch( CANCEL_URL, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                            body: body.toString(),
                        } ).catch( function () {} );
                    },
                } );
            } );
        }, [] );

        useEffect( function () {
            var unsubscribe = eventRegistration.onPaymentSetup( async function () {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: { paymentMethodData: {} },
                };
            } );
            return unsubscribe;
        }, [] );

        return createElement(
            'div', { className: 'wc-block-bkash-checkout' },
            DESCRIPTION ? createElement( 'p', null, DESCRIPTION ) : null,
            createElement( 'p', { style: { marginTop: '8px' } },
                createElement( 'strong', null,
                    __( 'You will be redirected to bKash to complete your payment.', 'bkash-for-woocommerce' )
                )
            )
        );
    }

    /* -----------------------------------------------------------------------
     * 8. Edit component - block editor (Gutenberg) preview only.
     * -------------------------------------------------------------------- */
    function BkashEdit() {
        return createElement(
            'div', null,
            ICON_URL
                ? createElement( 'img', { src: ICON_URL, alt: TITLE, style: { height: '24px', marginRight: '8px', verticalAlign: 'middle' } } )
                : null,
            createElement( 'strong', null, TITLE ),
            DESCRIPTION ? createElement( 'p', { style: { marginTop: '4px' } }, DESCRIPTION ) : null
        );
    }

    /* -----------------------------------------------------------------------
     * 9. Content router - picks component by integration type.
     * -------------------------------------------------------------------- */
    function BkashContent( props ) {
        if (
            INTEGRATION_TYPE === 'tokenized' ||
            INTEGRATION_TYPE === 'tokenized-both' ||
            INTEGRATION_TYPE === 'checkout-url'
        ) {
            return createElement( TokenizedContent, props );
        }
        return createElement( CheckoutContent, props );
    }

    /* -----------------------------------------------------------------------
     * 10. Register with WooCommerce Blocks.
     *
     *  CRITICAL: `content` and `edit` must be React *elements*
     *  (return value of createElement), NOT component functions.
     *  WC Blocks uses React.cloneElement() to inject eventRegistration /
     *  emitResponse props at render time.  Passing function references
     *  causes the payment method to be silently rejected.
     * -------------------------------------------------------------------- */
    registerPaymentMethod( {
        name:      SLUG,
        label:     createElement( PaymentLabel, null ),
        ariaLabel: TITLE,
        content:   createElement( BkashContent, null ),
        edit:      createElement( BkashEdit,    null ),
        placeOrderButtonLabel: __( 'Pay with bKash', 'bkash-for-woocommerce' ),
        canMakePayment: function () { return true; },
        supports: { features: FEATURES },
    } );

} )();
