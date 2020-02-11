<main class="container-fluid row justify-content-center" role="main">
    <section id="content-home" class="sectionMain row justify-content-center col-lg-10 col-xl-10">
        <div class="row justify-content-center col-lg-10 col-xl-10">
            <header id="header-main" class="headerMain col-lg-10 col-xl-10">
                <h5 class="headerMain__title">Excelbus Plugin</h5>
                <p class="headerMain__subtitle"><?php _e('Extraia dados de uma planilha e transforme em publicações', 'TEXT_DOMAIN'); ?></p>
            </header>

            <?php ImportImob::reader_xml(); ?>
        </div>
    </section>
</main>