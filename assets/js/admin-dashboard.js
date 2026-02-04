jQuery(function($){
    const $content = $('#sg365-tab-content');
    const $tabs = $('.sg365-tab-btn');

    function loadTab(tab, filter){
        $tabs.removeClass('is-active');
        $tabs.filter(`[data-tab="${tab}"]`).addClass('is-active');
        $content.html('<div class="sg365-loading">Loading…</div>');
        $.post(sg365CpAdmin.ajaxUrl, {
            action: 'sg365_cp_dashboard_tab',
            tab: tab,
            filter: filter || '',
            _wpnonce: sg365CpAdmin.nonce
        }).done(function(res){
            if(res.success){
                $content.html(res.data.html);
            } else {
                const msg = res.data && res.data.message ? res.data.message : 'Error loading tab.';
                $content.html('<div class="notice notice-error"><p>' + msg + '</p></div>');
            }
        }).fail(function(){
            $content.html('<div class="notice notice-error"><p>Error loading tab.</p></div>');
        });
    }

    const defaultTab = $('.sg365-tabs-nav').data('default') || 'overview';
    if($content.length){
        loadTab(defaultTab);
    }

    $(document).on('click', '.sg365-tab-btn', function(){
        loadTab($(this).data('tab'));
    });

    $(document).on('click', '.sg365-kpi-card', function(){
        loadTab('analytics', $(this).data('filter'));
    });

    $('#sg365-add-worklog').on('click', function(){
        $('#sg365-worklog-modal').show();
    });

    $('#sg365-worklog-cancel').on('click', function(){
        $('#sg365-worklog-modal').hide();
    });

    $('#sg365-worklog-client').on('change', function(){
        const clientId = $(this).val();
        const $site = $('#sg365-worklog-site');
        $site.html('<option value="">Loading…</option>');
        $.post(sg365CpAdmin.ajaxUrl, {
            action: 'sg365_cp_sites_for_client',
            client_id: clientId,
            _wpnonce: sg365CpAdmin.nonce
        }).done(function(res){
            if(res.success){
                let options = '<option value="">Select site</option>';
                res.data.options.forEach(function(opt){
                    options += '<option value="' + opt.id + '">' + opt.label + '</option>';
                });
                $site.html(options);
            }
        });
    });

    $('#sg365-worklog-form').on('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        $.ajax({
            url: sg365CpAdmin.ajaxUrl,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res){
                if(res.success){
                    $('#sg365-worklog-modal').hide();
                    loadTab($('.sg365-tab-btn.is-active').data('tab') || 'overview');
                } else {
                    const msg = res.data && res.data.message ? res.data.message : 'Error saving log.';
                    alert(msg);
                }
            },
            error: function(){
                alert('Error saving log.');
            }
        });
    });

    $('#sg365-add-service-type').on('click', function(){
        const $table = $('.sg365-service-types tbody');
        const index = $table.find('tr').length + 1;
        const staffOptions = $('#sg365-staff-options').html() || '';
        const row = `
            <tr>
                <td><input type="text" name="sg365_service_types[${index}][key]" /></td>
                <td><input type="text" name="sg365_service_types[${index}][label]" /></td>
                <td><select multiple name="sg365_service_types[${index}][staff][]" class="sg365-multiselect">${staffOptions}</select></td>
                <td><button type="button" class="button-link sg365-remove-row">×</button></td>
            </tr>`;
        $table.append(row);
    });

    $(document).on('click', '.sg365-remove-row', function(){
        $(this).closest('tr').remove();
    });

    $('#sg365-send-test-email').on('click', function(){
        const $status = $('#sg365-email-test-status');
        $status.text('Sending…');
        $.post(sg365CpAdmin.ajaxUrl, {
            action: 'sg365_cp_send_test_email',
            _wpnonce: sg365CpAdmin.nonce
        }).done(function(res){
            if(res.success){
                $status.text(res.data.message);
            } else {
                const msg = res.data && res.data.message ? res.data.message : 'Failed';
                $status.text(msg);
            }
        }).fail(function(){
            $status.text('Failed');
        });
    });
});
