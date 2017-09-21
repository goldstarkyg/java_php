<!---Footer Section Start Here-->

<div class="copyright-wrap" style="bottom: 0px;">
    <div class="container">
        <div class="col-md-12">
            <p>Copyright © 2017. All rights reserved.By Gold Starkyg  </p>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        /*----------------------------------------------------*/
        /*  Animation Progress Bars
         /*----------------------------------------------------*/

        $("[data-progress-animation]").each(function() {

            var $this = $(this);

            $this.appear(function() {

                var delay = ($this.attr("data-appear-animation-delay") ? $this.attr("data-appear-animation-delay") : 1);

                if(delay > 1) $this.css("animation-delay", delay + "ms");

                setTimeout(function() { $this.animate({width: $this.attr("data-progress-animation")}, 800);}, delay);

            }, {accX: 0, accY: -50});

        });
    });
</script>
<!---JS  -->
<script type="text/javascript" src="<?= asset('/js/test.js') ?>"></script>
<script type="text/javascript" src="<?= asset('/js/contact.js') ?>"></script>
<script type="text/javascript" src="<?= asset('/js/smoothscroll.js') ?>"></script>
<script type="text/javascript" src="<?= asset('/js/script.js') ?>"></script>
<script type="text/javascript" src="<?= asset('/js/owl.carousel.min.js') ?>"></script>
<script src="<?= asset('/js/bootstrap.offcanvas.js') ?>"></script>

<!-- AngularJS Application Scripts -->
<!--<script src="<? //= asset('/app.js') ?>"></script>-->


</body>
</html>