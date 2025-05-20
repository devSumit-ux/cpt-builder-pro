jQuery(document).ready(function($) {
    // CPT Rewrite Slug Toggle
    var $cptRewriteCheckbox = $('#cpt_rewrite_checkbox');
    var $cptRewriteSlugContainer = $('#cpt_rewrite_slug_container');

    function toggleCptRewriteSlug() {
        if ($cptRewriteCheckbox.is(':checked')) {
            $cptRewriteSlugContainer.slideDown(200);
        } else {
            $cptRewriteSlugContainer.slideUp(200);
        }
    }
    if ($cptRewriteCheckbox.length) {
        if (!$cptRewriteCheckbox.is(':checked')) { $cptRewriteSlugContainer.hide(); }
        $cptRewriteCheckbox.on('change', toggleCptRewriteSlug);
    }

    // Taxonomy Rewrite Slug Toggle
    var $taxRewriteCheckbox = $('#tax_rewrite_checkbox');
    var $taxRewriteSlugContainer = $('#tax_rewrite_slug_container');

    function toggleTaxRewriteSlug() {
        if ($taxRewriteCheckbox.is(':checked')) {
            $taxRewriteSlugContainer.slideDown(200);
        } else {
            $taxRewriteSlugContainer.slideUp(200);
        }
    }
    if ($taxRewriteCheckbox.length) {
        if (!$taxRewriteCheckbox.is(':checked')) { $taxRewriteSlugContainer.hide(); }
        $taxRewriteCheckbox.on('change', toggleTaxRewriteSlug);
    }

    // CPT Menu Icon Custom Input Toggle
    var $menuIconSelect = $('#menu_icon_select');
    var $menuIconCustomInput = $('#menu_icon_custom');

    function toggleMenuIconCustom() {
        if ($menuIconSelect.val() === 'custom') {
            $menuIconCustomInput.slideDown(200);
        } else {
            $menuIconCustomInput.slideUp(200);
        }
    }
    if ($menuIconSelect.length) {
        if ($menuIconSelect.val() !== 'custom') { $menuIconCustomInput.hide(); }
        $menuIconSelect.on('change', toggleMenuIconCustom);
    }
});