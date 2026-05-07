<?php
session_start();
$base_path = '../';
require_once $base_path . 'config/db.php';
require_once $base_path . 'includes/lang.php';

require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/navbar.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-primary text-white py-3 rounded-top-4">
                    <h3 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i><?php echo __('history'); ?></h3>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if ($_SESSION['lang'] == 'ar'): ?>
                        <h4 class="fw-bold mb-4 text-primary">لمحة تاريخية عن جماعة بني شكدال</h4>
                        <p class="lead" style="line-height: 1.8;">
                            تعتبر الجماعة القروية بني شكدال من الجماعات العريقة التي تتميز بتاريخها الغني وتراثها الثقافي الأصيل. 
                            لعبت المنطقة دوراً هاماً في التاريخ المحلي، وتميزت بتركيبتها الاجتماعية المتماسكة وتقاليدها العريقة.
                        </p>
                        <p style="line-height: 1.8;">
                            تتميز بني شكدال بموقعها الجغرافي الاستراتيجي، وقد شهدت عبر التاريخ محطات مهمة ساهمت في تشكيل هويتها الحالية. 
                            يعتمد سكانها تاريخياً على الفلاحة والتجارة المحلية، مما جعلها مركزاً حيوياً في محيطها.
                        </p>
                        <p style="line-height: 1.8;">
                            اليوم، تتطلع جماعة بني شكدال إلى مستقبل مشرق معتمدة على سواعد أبنائها، وتسعى لتطوير بنياتها التحتية والخدمات المقدمة للمواطنين مع الحفاظ على أصالتها وتراثها اللامادي.
                        </p>
                    <?php else: ?>
                        <h4 class="fw-bold mb-4 text-primary">Aperçu Historique de la Commune de Beni Chegdal</h4>
                        <p class="lead" style="line-height: 1.8;">
                            La commune rurale de Beni Chegdal est l'une des communes anciennes caractérisée par sa riche histoire et son patrimoine culturel authentique.
                            La région a joué un rôle important dans l'histoire locale et s'est distinguée par sa structure sociale cohésive et ses traditions séculaires.
                        </p>
                        <p style="line-height: 1.8;">
                            Beni Chegdal se distingue par sa position géographique stratégique. À travers l'histoire, elle a connu des étapes importantes qui ont contribué à façonner son identité actuelle.
                            Ses habitants se sont historiquement appuyés sur l'agriculture et le commerce local, ce qui en a fait un centre vital dans son environnement.
                        </p>
                        <p style="line-height: 1.8;">
                            Aujourd'hui, la commune de Beni Chegdal se tourne vers un avenir prometteur en s'appuyant sur les efforts de ses enfants. Elle cherche à développer ses infrastructures et les services offerts aux citoyens tout en préservant son authenticité et son patrimoine immatériel.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>
