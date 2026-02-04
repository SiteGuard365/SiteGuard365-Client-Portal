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

function sg365_cp_get_service_types(): array {
    $defaults = array(
        array( 'key' => 'development', 'label' => 'Development', 'staff' => array() ),
        array( 'key' => 'security', 'label' => 'Security', 'staff' => array() ),
        array( 'key' => 'seo', 'label' => 'SEO', 'staff' => array() ),
        array( 'key' => 'maintenance', 'label' => 'Maintenance', 'staff' => array() ),
        array( 'key' => 'support', 'label' => 'Support', 'staff' => array() ),
        array( 'key' => 'storage', 'label' => 'Storage', 'staff' => array() ),
    );
    $stored = get_option( 'sg365_service_types', array() );
    if ( ! is_array( $stored ) || empty( $stored ) ) {
        return $defaults;
    }
    return array_values( array_filter( $stored, function( $item ) {
        return ! empty( $item['key'] ) && ! empty( $item['label'] );
    } ) );
}

function sg365_cp_get_client_plan_type( int $client_id, int $user_id = 0 ): string {
    $plan = (string) get_post_meta( $client_id, '_sg365_plan_type', true );
    if ( $plan ) {
        return $plan;
    }
    if ( ! $user_id ) {
        $user_id = sg365_cp_get_user_id_for_client( $client_id );
    }
    if ( $user_id && sg365_cp_is_woocommerce_active() ) {
        $orders = wc_get_orders( array(
            'customer_id' => $user_id,
            'limit'       => 5,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'status'      => array( 'completed', 'processing', 'on-hold' ),
        ) );
        foreach ( $orders as $order ) {
            $meta = $order->get_meta( 'sg365_plan_type', true );
            if ( ! $meta ) {
                $meta = $order->get_meta( '_sg365_plan_type', true );
            }
            if ( $meta ) {
                return (string) $meta;
            }
        }
    }
    return 'maintenance';
}

function sg365_cp_client_has_work_access( int $client_id, int $user_id = 0 ): bool {
    $plan = sg365_cp_get_client_plan_type( $client_id, $user_id );
    return in_array( $plan, array( 'salary', 'project' ), true );
}

function sg365_cp_get_email_settings(): array {
    $defaults = array(
        'worklog_client' => array(
            'enabled' => 1,
            'subject' => 'New work log added for {client_name}',
            'body'    => '<p>Hello {client_name},</p><p>A new work log was added for {domain} on {log_date}.</p><p>{log_title}</p><p>{portal_link}</p>',
        ),
        'site_client' => array(
            'enabled' => 1,
            'subject' => 'New site added: {domain}',
            'body'    => '<p>Hello {client_name},</p><p>Your site {domain} was added to the portal.</p><p>{portal_link}</p>',
        ),
        'salary_due_soon' => array(
            'enabled' => 1,
            'days'    => 5,
            'subject' => 'Salary due soon for {client_name}',
            'body'    => '<p>Salary of {amount} is due on {due_date}.</p>',
        ),
        'salary_overdue' => array(
            'enabled' => 1,
            'days'    => 0,
            'subject' => 'Salary overdue for {client_name}',
            'body'    => '<p>Salary of {amount} was due on {due_date}.</p>',
        ),
        'salary_status' => array(
            'enabled' => 1,
            'subject' => 'Salary status updated for {client_name}',
            'body'    => '<p>Status updated for {client_name}: {amount} due on {due_date}.</p>',
        ),
    );
    $stored = get_option( 'sg365_cp_email_settings', array() );
    if ( ! is_array( $stored ) ) {
        return $defaults;
    }
    return array_replace_recursive( $defaults, $stored );
}

function sg365_cp_record_email_sent( string $key ): void {
    $sent = get_option( 'sg365_cp_email_last_sent', array() );
    if ( ! is_array( $sent ) ) {
        $sent = array();
    }
    $sent[ $key ] = time();
    update_option( 'sg365_cp_email_last_sent', $sent );
}

function sg365_cp_email_already_sent( string $key ): bool {
    $sent = get_option( 'sg365_cp_email_last_sent', array() );
    return is_array( $sent ) && isset( $sent[ $key ] );
}

function sg365_cp_email_template_replace( string $template, array $data ): string {
    foreach ( $data as $key => $value ) {
        $template = str_replace( '{' . $key . '}', (string) $value, $template );
    }
    return $template;
}

function sg365_cp_get_portal_link( int $client_id = 0 ): string {
    if ( sg365_cp_is_woocommerce_active() && function_exists( 'wc_get_account_endpoint_url' ) ) {
        return wc_get_account_endpoint_url( 'sg365-portal' );
    }
    return admin_url( 'admin.php?page=sg365-cp-dashboard' );
}

function sg365_cp_send_email( string $trigger, string $to, array $data, string $unique_key ): bool {
    $settings = sg365_cp_get_email_settings();
    if ( empty( $settings[ $trigger ]['enabled'] ) ) {
        return false;
    }
    $key = $trigger . '_' . $unique_key;
    if ( sg365_cp_email_already_sent( $key ) ) {
        return false;
    }
    $subject = sg365_cp_email_template_replace( $settings[ $trigger ]['subject'], $data );
    $body = sg365_cp_email_template_replace( $settings[ $trigger ]['body'], $data );
    $sent = wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
    if ( $sent ) {
        sg365_cp_record_email_sent( $key );
    }
    return $sent;
}
