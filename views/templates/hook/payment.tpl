{*
* NOTICE OF LICENSE
*
* This file is licenced under the Software License Agreement.
* With the purchase or the installation of the software in your application
* you accept the licence agreement.
*
* You must not modify, adapt or create derivative works of this source code
*
*  @author    tpay.com
*  @copyright 2010-2016 tpay.com
*  @license   LICENSE.txt
*}

{foreach $payments as $p}
<div id="tpay-row" class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a class="tpay" href="{$p.paymentLink|escape:'htmlall':'UTF-8'}" title="{$p.title|escape:'htmlall':'UTF-8'}">
                <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/tpay_logo_230.png"
                     alt="{l s='Logo tpay' mod='tpay'}" width="182" height="60"/>
                {$p.title|escape:'htmlall':'UTF-8'} <span>{if isset($surcharge)}({l s='surcharge ' mod='tpay'}{displayPrice price=$surcharge}){/if}</span>
            </a>
        </p>
    </div>
</div>
{/foreach}
