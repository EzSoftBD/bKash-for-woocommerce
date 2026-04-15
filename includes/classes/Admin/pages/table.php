<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function bkash_fw_key_to_label( $str, $separator = "_" ) {
	$str = str_replace( $separator, " ", $str );

	return ucwords( $str );
}

function bkash_fw_if_refund_value_is_present( $row, $column ) {
	return isset( $column[0] ) && str_contains( strtolower( $column[0] ), "refund" )
	       && ! empty( $row->{$column[0]} );
}

function bkash_fw_set_status_color( $status ) {
	$color = "#909090";

	if ( stripos( $status, "cancel" ) !== false ) {
		$color = "#f4a938";
	} else if ( stripos( $status, "complete" ) !== false ) {
		$color = "#1dae5b";
	} else if ( stripos( $status, "fail" ) !== false ) {
		$color = "#ff4136";
	} else if ( stripos( $status, "auth" ) !== false ) {
		$color = "#0b608a";
	}

	return $color;

}

?>

	<div class="wrap abs">
		<h2><?php echo esc_html( $title ?? 'List' ); ?></h2>

        <!-- Search Form -->
        <div class="tablenav top">
            <div class="alignleft actions">

                <form action="#" method="GET">
					<?php
					if ( isset( $filters ) && count( $filters ) > 0 ) {
					foreach ( $filters as $key => $filter ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						$old_input = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : ""; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound,WordPress.Security.NonceVerification.Recommended
							?>
									<input
									type='text'
									name='<?php echo esc_attr( $key ); ?>'
									value='<?php echo esc_attr( $old_input ); ?>'
									placeholder='<?php echo esc_attr( $filter ); ?>'/>
							<?php
						}
					}

					$page_name = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound,WordPress.Security.NonceVerification.Recommended
					?>
					      <input type='hidden' name='page'
						      value='<?php echo esc_attr( $page_name ); ?>'/>
                    <button type="submit">Search</button>
                </form>


            </div>
            <br class="clear">
        </div>

        <!-- Table -->
        <table id="transaction-list-table" class='wp-list-table widefat fixed striped posts'
			   aria-describedby="<?php echo esc_attr( $title ); ?>">
            <!-- Column Headers -->
            <tr>
				<?php
				if ( isset( $columns ) ) {
						foreach ( array_keys( $columns ) as $table_head ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						?>
							<th class='manage-column ss-list-width' scope='col'>
							<?php echo esc_html( $table_head ); ?>
						</th>
						<?php
					}

					if ( isset( $actions ) && count( $actions ) > 0 ) {
						?>
                        <th class='manage-column ss-list-width' scope='col'>
                            Actions
                        </th>
						<?php
					}
				}
				?>
            </tr>

			<?php
			if ( isset( $rows ) && count( (array) $rows ) > 0 ) {
				foreach ( $rows as $row ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				?>
                    <!-- Items -->
                    <tr>
						<?php
						foreach ( $columns as $column ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
							// if want to show multiple value in a single column
							if ( is_array( $column ) ) { ?>
                                <td class='manage-column ss-list-width'>
									<?php
									if ( bkash_fw_if_refund_value_is_present( $row, $column ) ) {
										?>
                                        <span class="bKash-chip">Refunded</span>
										<?php
									}

									foreach ( $column as $item ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
										if ( ! empty( $row->{$item} ) ) {
											?>
                                            <p>
												<?php
												$constructed_value = bkash_fw_key_to_label( $item ) . ": " . $row->{$item}; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
												echo esc_html( $constructed_value );
												?>
                                            </p>
											<?php
										}
									}
									?>
                                </td>
								<?php
							} else { // single value in a column
								?>
                                <td class='manage-column ss-list-width'>
									<?php
									if ( str_contains( strtolower( $column ), "status" ) ) {
										?>
																				<span class="bKash-chip"
																							style="background: <?php echo esc_attr( bkash_fw_set_status_color( $row->{$column} ) ); ?> !important;">
																						<?php echo esc_html( $row->{$column} ); ?>
																				</span>
										<?php
									} else {
										echo esc_html( $row->{$column} );
									}
									?>
                                </td>
								<?php
							}
						}
						?>
                        <!-- Action Buttons -->
						<?php if ( isset( $actions ) && count( $actions ) > 0 ) { ?>
                            <td class='manage-column ss-list-width'>
								<?php
								foreach ( $actions as $action ) {
									?>
                                    <a
										<?php
										if ( isset( $action['confirm'] ) && $action['confirm'] ) {
											echo 'onclick="return confirm(\'Are you sure to do this?\');"';
										}
										?>
                                            href="<?php echo esc_url(
												admin_url( 'admin.php?page=' . BKASH_FW_ADMIN_PAGE_SLUG . '/' . ( $action['page'] ?? '' )
												           . '&action=' . ( $action['action'] ?? '' ) . '&id=' . $row->ID )
											); ?>">
										<?php echo esc_html( $action['title'] ?? '' ); ?>
                                    </a>
									<?php
								}
								?>
                            </td>
						<?php } ?>
                    </tr>
				<?php }
			} else {
				echo "<tr><td colspan='" . count( $columns ) . "'>No records found</td></tr>";
			} ?>
        </table>
    </div>

<?php
if ( isset( $page_links ) && $page_links ) {
	?>
    <div class="tablenav pagination-links" style="width: 99%;">
		<div class="tablenav-pages" style="margin: 1em 0"><?php echo wp_kses_post( $page_links ); ?></div>
    </div>
	<?php
}
?>