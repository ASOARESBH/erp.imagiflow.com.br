<?php

use App\Core\Lang;
use App\Core\View;

$currentLocale = Lang::instance()->current();
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$languageCodes = [
    'pt_BR' => 'PT',
    'en' => 'EN',
    'es' => 'ES',
];
?>
<form method="post" action="/idioma" class="language-selector d-inline-flex align-items-center gap-1" aria-label="<?php echo htmlspecialchars(t('common.language')); ?>">
    <?php echo View::csrfField(); ?>
    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($currentUri); ?>">
    <span class="visually-hidden"><?php echo htmlspecialchars(t('common.language')); ?></span>
    <?php foreach (Lang::instance()->supported() as $locale => $label): ?>
        <button type="submit"
                name="locale"
                value="<?php echo htmlspecialchars($locale); ?>"
                class="btn btn-sm <?php echo $currentLocale === $locale ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                aria-label="<?php echo htmlspecialchars($label); ?>"
                aria-pressed="<?php echo $currentLocale === $locale ? 'true' : 'false'; ?>">
            <?php echo $languageCodes[$locale] ?? strtoupper($locale); ?>
        </button>
    <?php endforeach; ?>
</form>
