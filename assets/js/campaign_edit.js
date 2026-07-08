jQuery(document).ready(function ($) {
    // Function to toggle visibility of dropdowns based on selected radio button
    function toggleDropdowns() {
        if ($('#campaign_type').val() == 'rss_reader') {
            var selectedValue = $('input[name="campaign_rss_feed_reader"]:checked').val();

            // Show the corresponding dropdown based on the selected value
            if (selectedValue === 'the_content') {
                $('#custom-dropdown-posts').show();
                $('#custom-dropdown-pages').hide();
                $('#rss_page_template').hide();
                toggleCustomTypes();
                $('input[name="campaign_customposttype"]').not(':radio[name="campaign_customposttype"][value="page"]').prop('disabled', false);
            } else if (selectedValue === 'page_template') {
                $('#custom-dropdown-pages').show();
                if($('#campaign_page_select').val()){
                    $('#rss_page_template').show();
                }
                $('#custom-dropdown-posts').hide();
                
                $('input[name="campaign_customposttype"]').not(':radio[name="campaign_customposttype"][value="page"]').prop('disabled', true);

                $(':radio[name="campaign_customposttype"][value="page"]').prop('checked', true);
            } else {
                $('#custom-dropdown-posts').hide();
                $('#custom-dropdown-pages').hide();
                $('#rss_page_template').hide();

                $('input[name="campaign_customposttype"]').not(':radio[name="campaign_customposttype"][value="page"]').prop('disabled', false);
            }
        }
    }

    // Initial call to toggleDropdowns to set initial state
    toggleDropdowns();
    toggleCustomTypes();

    function toggleCustomTypes() {
        if ($('#campaign_type').val() == 'rss_reader') {
            var postTypes = $('input[name="campaign_customposttype"]:checked').val();
            var selectedValue = $('input[name="campaign_rss_feed_reader"]:checked').val();

            if (selectedValue !== 'shortcode') {
                if (selectedValue) {
                    if (postTypes == 'post') {
                        $('#custom-dropdown-posts').show();
                        $('#custom-dropdown-pages').hide();
                    } else if (postTypes == 'page') {
                        $('#custom-dropdown-pages').show();
                        $('#custom-dropdown-posts').hide();
                    } else {
                        $('#custom-dropdown-posts').hide();
                        $('#custom-dropdown-pages').hide();
                    }
                }
            }
        }
    }

    $('input[name="campaign_customposttype"]').on('change', function () {
        if ($('#campaign_type').val() == 'rss_reader') {
            toggleCustomTypes();
        }
    });
    // Call toggleDropdowns whenever the radio buttons change
    $('input[name="campaign_rss_feed_reader"]').on('change', function () {
        if ($('#campaign_type').val() == 'rss_reader') {
            toggleDropdowns();
        }
    });

    $('#campaign_type').on('change', function () {
        if ($('#campaign_type').val() != 'rss_reader') {
            $('#custom-dropdown-posts').hide();
            $('#custom-dropdown-pages').hide();
        }else{
            toggleCustomTypes();
        }
    });
    $('#campaign_page_select').on('change', function () {
        if($('#campaign_page_select').val()){
            toggleDropdowns();
        }else{
            $('#rss_page_template').hide();
        }
    });
    $('#campaign_max_to_show , #campaign_max').on('blur', function (){
        if ($('#campaign_type').val() == 'rss_reader') {
            if ($('#campaign_max_to_show').val() !== $('#campaign_max').val()) {
                $('#fieldserror').remove();
                $("#poststuff").prepend('<div id="fieldserror" class="error fade">ERROR: ' + backend_object_rss.error_message + '</div>');
            } else {
                $('#fieldserror').remove();
            }
        }
    });

    // ---- Layout picker ----
    (function () {
        var $cards = $('.wpe_rss-layout-card');
        if (!$cards.length) {
            return;
        }
        var $radios = $('.wpe_rss-layout-radio');
        var $textarea = $('#campaign_rss_html_content');
        var $note = $('.wpe_rss-layout-custom-note');
        var $toggle = $('.wpe_rss-advanced-toggle');
        var $panel = $('#wpe_rss-template-panel');
        var $label = $('.wpe_rss-advanced-label');
        var presets = (backend_object_rss && backend_object_rss.presets) || {};

        function norm(s) { return (s || '').replace(/\s+/g, ' ').replace(/'/g, '"').trim(); }
        function currentLayout() { return $radios.filter(':checked').val() || 'list'; }
        function presetFor(layout) { return presets[layout] || ''; }
        function isCustom(layout) { return norm($textarea.val()) !== norm(presetFor(layout)); }

        var previousLayout = currentLayout();

        function refreshNote() { $note.toggle(isCustom(currentLayout())); }

        function openPanel(open) {
            $panel.toggle(open);
            $toggle.attr('aria-expanded', open ? 'true' : 'false').toggleClass('is-open', open);
            $label.text(open ? backend_object_rss.hide_html : backend_object_rss.show_html);
        }

        $radios.on('change', function () {
            var layout = $(this).val();
            if (isCustom(previousLayout) && !window.confirm(backend_object_rss.confirm_overwrite)) {
                $radios.filter('[value="' + previousLayout + '"]').prop('checked', true);
                return;
            }
            $textarea.val(presetFor(layout));
            $cards.removeClass('is-selected');
            $(this).closest('.wpe_rss-layout-card').addClass('is-selected');
            previousLayout = layout;
            refreshNote();
        });

        $textarea.on('input', refreshNote);
        $toggle.on('click', function () { openPanel($panel.is(':hidden')); });

        refreshNote();
        if (isCustom(currentLayout())) {
            openPanel(true);
        }
    })();
});