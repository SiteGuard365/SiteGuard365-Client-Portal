(function($){
    const panel = $('#sg365-cp-tab-panel');
    const tabs = $('.sg365-cp-tab');
    const container = $('.sg365-cp-tabs');
    const nonce = container.data('nonce');
    let currentTab = 'overview';

    function loadTab(tab, filter){
        currentTab = tab;
        tabs.removeClass('is-active').filter(`[data-tab="${tab}"]`).addClass('is-active');
        panel.html('<div class="sg365-cp-loading">Loading dashboard…</div>');
        $.post(ajaxurl, { action: 'sg365_cp_dashboard_tab', nonce: nonce, tab: tab, filter: filter || '' })
            .done(function(response){
                if(response.success){
                    panel.html(response.data.html);
                } else {
                    panel.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            });
    }

    tabs.on('click', function(){
        loadTab($(this).data('tab'));
    });

    panel.on('click', '.sg365-cp-kpi-card', function(){
        const filter = $(this).data('filter');
        loadTab('analytics', filter);
    });

    panel.on('click', '#sg365-cp-open-worklog', function(){
        $('#sg365-cp-worklog-modal').addClass('is-open').attr('aria-hidden','false');
    });

    $(document).on('click', '[data-modal-close="1"]', function(){
        $('#sg365-cp-worklog-modal').removeClass('is-open').attr('aria-hidden','true');
    });

    $('#sg365-cp-worklog-form').on('change', 'select[name="client_id"]', function(){
        const clientId = $(this).val();
        $.post(ajaxurl, { action: 'sg365_cp_client_sites', nonce: $('input[name="nonce"]').val(), client_id: clientId })
            .done(function(response){
                const select = $('#sg365-cp-worklog-form select[name="site_id"]');
                select.empty();
                if(response.success && response.data.sites.length){
                    select.append('<option value="0">— Select —</option>');
                    response.data.sites.forEach(function(site){
                        select.append('<option value="' + site.id + '">' + site.label + '</option>');
                    });
                } else {
                    select.append('<option value="0">No sites found</option>');
                }
            });
    });

    $('#sg365-cp-worklog-form').on('submit', function(e){
        e.preventDefault();
        const form = $(this);
        const notice = form.find('.sg365-cp-modal__notice');
        notice.text('');
        $.post(ajaxurl, form.serialize())
            .done(function(response){
                if(response.success){
                    notice.text(response.data.message);
                    form[0].reset();
                    $('#sg365-cp-worklog-modal').removeClass('is-open').attr('aria-hidden','true');
                    loadTab(currentTab);
                } else {
                    notice.text(response.data.message || 'Something went wrong.');
                }
            });
    });

    loadTab(currentTab);
})(jQuery);
