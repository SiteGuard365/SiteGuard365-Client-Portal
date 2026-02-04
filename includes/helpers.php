<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function sg365_cp_is_woocommerce_active(): bool {
    return class_exists( 'WooCommerce' );
}

function sg365_cp_current_user_can_manage(): bool {
    return current_user_can( 'manage_options' ) || current_user_can( 'sg365_cp_manage' );
}

function sg365_cp_get_client_id_for_user( int $user_id ): int {
    $q = new WP_Query(array(
        'post_type'      => 'sg365_client',
        'post_status'    => 'any',
        'fields'         => 'ids',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'   => '_sg365_user_id',
                'value' => $user_id,
            )
        )
    ));
    return ! empty( $q->posts ) ? (int) $q->posts[0] : 0;
}

function sg365_cp_get_user_id_for_client( int $client_id ): int {
    return (int) get_post_meta( $client_id, '_sg365_user_id', true );
}

function sg365_cp_clean_text( $val ): string {
    return sanitize_text_field( wp_unslash( (string) $val ) );
}

function sg365_cp_clean_bool( $val ): int {
    return ! empty( $val ) ? 1 : 0;
}

function sg365_cp_clean_int( $val ): int {
    return (int) $val;
}

function sg365_cp_clean_money( $val ): float {
    $val = preg_replace( '/[^0-9.\-]/', '', (string) $val );
    return (float) $val;
}

function sg365_cp_month_key_from_input( string $ym ): string {
    $ym = trim( $ym );
    if ( preg_match( '/^\d{4}-\d{2}$/', $ym ) ) {
        return $ym;
    }
    return gmdate( 'Y-m' );
}
