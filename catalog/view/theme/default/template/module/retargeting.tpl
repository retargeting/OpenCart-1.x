<!--
    VIEW FILE - Catalog template
    catalog/view/theme/default/template/module
    retargeting.tpl

    MODULE: Retargeting
-->
<!-- START RETARGETING EXTENSION-->
<script>

    (function(){
        ra_key = "<?php echo $api_key_field; ?>";
        ra_params = {
            add_to_cart_button_id: "<?php echo $retargeting_addToCart; ?>",
            price_label_id: "<?php echo $retargeting_mouseOverPrice; ?>"
        };
        var ra = document.createElement("script"); ra.type ="text/javascript"; ra.async = true; ra.src = ("https:" ==
        document.location.protocol ? "https://" : "http://") + "tracking.retargeting.biz/v3/rajs/" + ra_key + ".js";
        var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ra,s);
    })
    ();
    <?php echo $js_output; ?>
</script>
<!-- END RETARGETING EXTENSION v1.5.6.0 -->
