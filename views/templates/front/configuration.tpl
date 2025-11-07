<script type="text/javascript">
    jQuery(document).ready(function () {
        getValuesTpay();
        jQuery('#TPAY_CARD_MID_NB').change(function () {
            getValuesTpay();
        });
    });

    function getValuesTpay() {
        var tr = $('#fieldset_3_3 .form-wrapper .form-group'),
            id = jQuery("#TPAY_CARD_MID_NB option:selected").val(),
            mid = 0;
        if (id == 1) {
            mid = 2;
        } else {
            mid = (id - 1) * 9 + 2;
        }
        var maxMid = mid + 9;
        for (var n = 2; n < tr.length; n++) {
            tr[n].style.display = "none";
        }
        for (var o = mid; o < maxMid; o++) {
            tr[o].style.display = "";
        }
    }
</script>
