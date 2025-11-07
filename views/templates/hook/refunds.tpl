<div class="box" style="overflow: auto;">
    {if isset($tpay_refund_status)}
    {$tpay_refund_status}
    {/if}
    <div id="formAddTpayRefundPanel" class="panel">
        <div class="panel-heading">
            <i class="icon-money"></i>
            {l s='Tpay refunds' mod='tpay'}
        </div>
        <form id="formAddTpayRefund" method="post" action="">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th><span class="title_box ">{l s='Refund date' mod='tpay'}</span></th>
                        <th><span class="title_box ">{l s='Refunded transaction title' mod='tpay'}</span></th>
                        <th><span class="title_box ">{l s='Refund amount ' mod='tpay'}</span></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    {foreach $tpayRefunds as $tpayRefund}
                    <tr>
                        <td>{$tpayRefund.tpay_refund_date}</td>
                        <td>{$tpayRefund.tpay_transaction_id}</td>
                        <td>{$tpayRefund.tpay_refund_amount}</td>
                    </tr>
                    {/foreach}
                    <tr>
                        <td></td>
                        <td></td>
                        <td class="actions">
                            <input type="text" name="tpay_refund_amount" id="tpay_refund_amount"
                                   class="form-control fixed-width-sm" placeholder="1.00">
                        </td>
                        <td>
                            <input type="submit" class="btn btn-primary" value="{l s='Process refund' mod='tpay'}">
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
