<?php
function renderStudySpaceLoader($id = 'studyspace-loader', $showOverlay = true) {
    $overlayClass = $showOverlay ? 'loader-overlay' : 'loader-inline';
    return <<<HTML
    <div id="{$id}" class="{$overlayClass}" aria-label="Loading StudySpace">
        <div class="ss-loader-box">
            <div class="ss-book ss-blue"></div>
            <div class="ss-book ss-brown-1"></div>
            <div class="ss-book ss-brown-2"></div>
            <div class="ss-book ss-brown-3"></div>
        </div>
    </div>
HTML;
}
?>