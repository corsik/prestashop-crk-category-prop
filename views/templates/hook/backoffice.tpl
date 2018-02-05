{foreach from=$arrProperty key="k" item="property"}
    <div class="form-group">
        <label class="control-label col-lg-3">
            <span class="label-tooltip">
                {if $k == 'link'}
                    {l s='Ссылка на сайт продавца' mod='crk_category_prop'}
                {else}
                    {l s='Цвет фона' mod='crk_category_prop'}
                {/if}
            </span>
        </label>
        <div class="col-lg-4">
            <input type="text" name="property_{$k}" value="{$property}">
        </div>
        <div class="col-lg-6 col-lg-offset-3"></div>
    </div>
{/foreach}
