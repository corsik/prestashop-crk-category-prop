<div class="form-group">
{foreach from=$arrProperty key="k" item="property"}
    {if $property.property_type == 'input'}
        <label class="control-label col-lg-3">
            <span class="label-tooltip">{l s=$property.property_lang mod='crk_category_prop'}</span>
        </label>
        <div class="col-lg-9">
            <input type="text" name="property_{$property.id_property}" value="{$property.property_value}">
        </div>
    {else if $property.property_type == 'textarea'}
        <label class="control-label col-lg-3">
            <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="">
                {l s=$property.property_lang mod='crk_category_prop'}
            </span>
        </label>
        <div class="col-lg-9">
            <div class="input-group">																																																																		
                <span id="property_{$property.id_property}_counter" class="input-group-addon">160</span>
                <textarea name="property_{$property.id_property}" id="property_{$property.id_property}" class="textarea-autosize" maxlength="160" data-maxchar="160" style="overflow: hidden; word-wrap: break-word; resize: none; height: 48px;">{$property.property_value}</textarea>
                <script type="text/javascript">
                    $(document).ready(function () {
                        countDown($("#property_{$property.id_property}"), $("#property_{$property.id_property}_counter"));
                    });
                </script>
            </div>																
        </div>
    {/if}
{/foreach}
</div>