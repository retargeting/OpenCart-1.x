<!--
    VIEW FILE - Admin template
    admin/view/template/module
    retargeting.tpl

    MODULE: Retargeting
-->
<?php echo $header; ?>
<div id='content'>
    <div class='breadcrumb'>
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <?php echo $breadcrumb['separator']; ?><a href='<?php echo $breadcrumb['href']; ?>'><?php echo $breadcrumb['text']; ?></a>
        <?php } ?>
    </div>
    <?php if ($error_warning) { ?>
    <div class='warning'><?php echo $error_warning; ?></div>
    <?php } ?>
    <div class='box'>

        <div class='heading'>
            <h1><img src='view/image/module.png' alt='' /> <?php echo $heading_title; ?></h1>
            <div class='buttons'><a onclick='$("#form").submit();' class='button'><?php echo $button_save; ?></a><a href='<?php echo $cancel; ?>' class='button'><?php echo $button_cancel; ?></a></div>
        </div>

        <div class='content'>

            <form action='<?php echo $action; ?>' method='post' enctype='multipart/form-data' id='form'>

                <!-- API Key & Token -->
                <table class='form'>
                    <tr>
                        <td colspan='2'><img src='https://retargeting.ro/static/images/i/logo.png' alt='' /></td>
                    </tr>
                    <tr>
                        <td>
                            <span class='required'>*</span> API Key:
                        </td>
                        <td>
                            <input type='text' size='50' name='api_key_field' value='<?php echo $api_key_field; ?>' />
                            <?php if ($error_code) { ?>
                            <span class='error'><?php echo $error_code; ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class='required'>*</span> Token:
                        </td>
                        <td>
                            <input type='text' size='50' name='api_secret_field' value='<?php echo $api_secret_field; ?>' />
                            <?php if ($error_code) { ?>
                            <span class='error'><?php echo $error_code; ?></span>
                            <?php } ?>
                        </td>
                    </tr>

                    <!-- API URL -->
                    <tr>
                        <td>
                            <span class='required'>*</span> API URL:
                        </td>
                        <td>
                            <input type='text' size='50' value='<?php echo $shop_url; ?>' disabled />
                        </td>
                    </tr>

                    <!-- Help text -->
                    <tr>
                        <td colspan='2'>
                            <div class='help'>
                                The API key is available in your <a href='https://retargeting.biz/admin?action=api_redirect&token=028e36488ab8dd68eaac58e07ef8f9bf' target='_blank'>Retargeting account</a>. <br />
                                You need to register the <strong>API URL</strong> into your Retargeting account to automatically generate discount codes.
                            </div>
                        </td>
                    </tr>

                </table>


                <!-- Custom CSS -->
                <table class='form'>
                    <tr>
                        <td>
                            <h1>Fine tuning</h1>
                        </td>
                        <td>
                            <div class='help'>
                                Your OpenCart theme may alterate certain CSS and HTML elements that are important for Retargeting. Below you can adjust the CSS selectors which the Retargeting App will be monitoring. A detailed documentation is available at <a href='https://retargeting.biz/admin?action=api_redirect&token=5ac66ac466f3e1ec5e6fe5a040356997' target='_blank'>Retargeting: fine tuning</a>. Please use only single quotes. Example: input[type='text']
                            </div>
                        </td>
                    </tr>

                    <!-- setEmail -->
                    <tr>
                        <td>
                            <span class='required'>*</span> Listen for e-mail input:
                        </td>
                        <td>
                            <input type='text' size='50' name='retargeting_setEmail' value="<?php echo (isset($retargeting_setEmail) && !empty($retargeting_setEmail)) ? $retargeting_setEmail : 'input[type=\'text\']'; ?>" /> (setEmail)
                        </td>
                    </tr>

                    <!-- addToCart -->
                    <tr>
                        <td>
                            <span class='required'>*</span> Add to cart button:
                        </td>
                        <td>
                            <input type='text' size='50' name='retargeting_addToCart' value='<?php echo (isset($retargeting_addToCart) && !empty($retargeting_addToCart)) ? $retargeting_addToCart : "#button-cart"; ?>' /> (addToCart)
                        </td>
                    </tr>

                    <!-- clickImage -->
                    <tr>
                        <td>
                            <span class='required'>*</span> Main product image container:
                        </td>
                        <td>
                            <input type='text' size='50' name='retargeting_clickImage' value="<?php echo (isset($retargeting_clickImage) && !empty($retargeting_clickImage)) ? $retargeting_clickImage : '#image'; ?>" /> (clickImage)
                        </td>
                    </tr>

                    <!-- commentOnProduct -->
                    <tr>
                        <td>
                            <span class='required'>*</span> Review/Comments button:
                        </td>
                        <td>
                            <input type='text' size='50' name='retargeting_commentOnProduct' value="<?php echo (isset($retargeting_commentOnProduct) && !empty($retargeting_commentOnProduct)) ? $retargeting_commentOnProduct : '#button-review'; ?>" /> (commentOnProduct)
                        </td>
                    </tr>

                    <!-- mouseOverPrice -->
                    <tr>
                        <td>
                            <span class='required'>*</span> Product price container:
                        </td>
                        <td>
                            <input type='text' size='50' name='retargeting_mouseOverPrice' value="<?php echo (isset($retargeting_mouseOverPrice) && !empty($retargeting_mouseOverPrice)) ? $retargeting_mouseOverPrice : '.price'; ?>" /> (mouseOverPrice)
                        </td>
                    </tr>

                    <!-- setVariation -->
                    <tr>
                        <td>
                            <span class='required'>*</span> Product variation:
                        </td>
                        <td>
                            <input type='text' size='50' name='retargeting_setVariation' value='<?php echo (isset($retargeting_setVariation) && !empty($retargeting_setVariation)) ? $retargeting_setVariation : ".variation"; ?>' /> (setVariation)
                        </td>
                    </tr>

                </table>


                <!-- Layouts -->
                <table id='module' class='list'>
                    <thead>
                    <tr>
                        <td class='left'><?php echo $entry_layout; ?></td>
                        <td class='left'><?php echo $entry_position; ?></td>
                        <td class='left'><?php echo $entry_status; ?></td>
                        <td class='right'><?php echo $entry_sort_order; ?></td>
                        <td></td>
                    </tr>
                    </thead>

                    <?php $module_row = 0; ?>
                    <?php foreach ($modules as $module) { ?>
                    <tbody id='module-row<?php echo $module_row; ?>'>
                    <tr>
                        <td class='left'><select name='retargeting_module[<?php echo $module_row; ?>][layout_id]'>
                                <?php foreach ($layouts as $layout) { ?>
                                <?php if ($layout['layout_id'] == $module['layout_id']) { ?>
                                <option value='<?php echo $layout['layout_id']; ?>' selected='selected'><?php echo $layout['name']; ?></option>
                                <?php } else { ?>
                                <option value='<?php echo $layout['layout_id']; ?>'><?php echo $layout['name']; ?></option>
                                <?php } ?>
                                <?php } ?>
                            </select></td>
                        <td class='left'><select name='retargeting_module[<?php echo $module_row; ?>][position]'>
                                <?php if ($module['position'] == 'content_top') { ?>
                                <option value='content_top' selected='selected'><?php echo $text_content_top; ?></option>
                                <?php } else { ?>
                                <option value='content_top'><?php echo $text_content_top; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'content_bottom') { ?>
                                <option value='content_bottom' selected='selected'><?php echo $text_content_bottom; ?></option>
                                <?php } else { ?>
                                <option value='content_bottom'><?php echo $text_content_bottom; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'column_left') { ?>
                                <option value='column_left' selected='selected'><?php echo $text_column_left; ?></option>
                                <?php } else { ?>
                                <option value='column_left'><?php echo $text_column_left; ?></option>
                                <?php } ?>
                                <?php if ($module['position'] == 'column_right') { ?>
                                <option value='column_right' selected='selected'><?php echo $text_column_right; ?></option>
                                <?php } else { ?>
                                <option value='column_right'><?php echo $text_column_right; ?></option>
                                <?php } ?>
                            </select></td>
                        <td class='left'><select name='retargeting_module[<?php echo $module_row; ?>][status]'>
                                <?php if ($module['status']) { ?>
                                <option value='1' selected='selected'><?php echo $text_enabled; ?></option>
                                <option value='0'><?php echo $text_disabled; ?></option>
                                <?php } else { ?>
                                <option value='1'><?php echo $text_enabled; ?></option>
                                <option value='0' selected='selected'><?php echo $text_disabled; ?></option>
                                <?php } ?>
                            </select></td>
                        <td class='right'><input type='text' name='retargeting_module[<?php echo $module_row; ?>][sort_order]' value='<?php echo $module['sort_order']; ?>' size='3' /></td>
                        <td class='left'><a onclick='$('#module-row<?php echo $module_row; ?>').remove();' class='button'><?php echo $button_remove; ?></a></td>
                    </tr>
                    </tbody>
                    <?php $module_row++; ?>
                    <?php } ?>
                    <tfoot>
                    <tr>
                        <td colspan='4'></td>
                        <td class='left'><a onclick='addModule();' class='button'><?php echo $button_add_module; ?></a></td>
                    </tr>
                    </tfoot>
                </table>
            </form>
        </div>
    </div>
</div>
<script type='text/javascript'>
    //<!--
    var module_row = <?php echo $module_row; ?>;

    function addModule() {
        html  = '<tbody id="module-row' + module_row + '">';
        html += '  <tr>';
        html += '    <td class="left"><select name="retargeting_module[' + module_row + '][layout_id]">';
    <?php foreach ($layouts as $layout) { ?>
            html += '      <option value="<?php echo $layout['layout_id']; ?>"><?php echo addslashes($layout['name']); ?></option>';
        <?php } ?>
        html += '    </select></td>';
        html += '    <td class="left"><select name="retargeting_module[' + module_row + '][position]">';
        html += '      <option value="content_top"><?php echo $text_content_top; ?></option>';
        html += '      <option value="content_bottom"><?php echo $text_content_bottom; ?></option>';
        html += '      <option value="column_left"><?php echo $text_column_left; ?></option>';
        html += '      <option value="column_right"><?php echo $text_column_right; ?></option>';
        html += '    </select></td>';
        html += '    <td class="left"><select name="retargeting_module[' + module_row + '][status]"ß>';
        html += '      <option value="1" selected="selected"><?php echo $text_enabled; ?></option>';
        html += '      <option value="0"><?php echo $text_disabled; ?></option>';
        html += '    </select></td>';
        html += '    <td class="right"><input type="text" name="retargeting_module[' + module_row + '][sort_order]" value="" size="3" /></td>';
        html += '    <td class="left"><a onclick="$(\'#module-row' + module_row + '\').remove();" class="button"><?php echo $button_remove; ?></a></td>';
        html += '  </tr>';
        html += '</tbody>';

        $('#module tfoot').before(html);

        module_row++;
    }
    //-->
    </script>
<?php echo $footer; ?>
