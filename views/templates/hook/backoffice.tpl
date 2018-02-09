{foreach from=$arrProperty key="k" item="property"}
    {if $property.property_type == 'input'}
        <div class="form-group">
            <label class="control-label col-lg-3">
                <span class="label-tooltip">{l s=$property.property_lang mod='crk_category_prop'}</span>
            </label>
            <div class="col-lg-9">
                <input type="text" name="property_{$property.id_property}" value="{$property.property_value}">
            </div>
        </div>
    {else if $property.property_type == 'textarea'}
        <div class="form-group">
            <label class="control-label col-lg-3">
                <span class="label-tooltip" data-toggle="tooltip" data-html="true">
                    {l s=$property.property_lang mod='crk_category_prop'}
                </span>
            </label>
            <div class="col-lg-9">
                <div class="input-group">																																																																		
                    <span id="property_{$property.id_property}_counter" class="input-group-addon">255</span>
                    <textarea name="property_{$property.id_property}" 
                              id="property_{$property.id_property}" 
                              class="textarea-autosize" 
                              maxlength="255" 
                              data-maxchar="255" 
                              style="overflow: hidden; word-wrap: break-word; resize: none; height: 48px;">{$property.property_value}</textarea>
                    <script type="text/javascript">
                        $(function() {
                            countDown($("#property_{$property.id_property}"), $("#property_{$property.id_property}_counter"));
                        });
                    </script>
                </div>																
            </div>
        </div>
    {else if $property.property_type == 'html'}
        <div class="form-group">
            <label class="control-label col-lg-3">
                <span class="label-tooltip" data-toggle="tooltip" data-html="true">
                    {l s=$property.property_lang mod='crk_category_prop'}
                </span>
            </label>
            <div class="col-lg-9">
                    <textarea name="property_{$property.id_property}" 
                              id="property_{$property.id_property}" 
                              style="overflow: hidden; word-wrap: break-word; resize: none; height: 48px;" 
                              class="rte textarea-autosize">{$property.property_value}</textarea>
            </div>
        </div>
        <script language="javascript" type="text/javascript">
            $(function() {
                    tinySetup();
            });
         </script>
    {/if}
{/foreach}
