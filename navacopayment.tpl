
<!-- navaco Payment Module -->
<p class="payment_module">
    <a href="javascript:$('#navaco').submit();" title="{l s='Online payment with navaco' mod='navaco'}">
        <img src="modules/rasacards/rasacards.png" alt="{l s='Online payment with navaco' mod='navaco'}" />
		{l s=' پرداخت توسط کلیه کارت های عضو شبکه شتاب بانکی ' mod='navaco'}
<br>
</a></p>

<form action="modules/navaco/cb_navaco.php?do=payment" method="post" id="navaco" class="hidden">
    <input type="hidden" name="orderId" value="{$orderId}" />
</form>
<br><br>
<!-- End of navaco Payment Module-->
