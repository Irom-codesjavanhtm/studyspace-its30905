<?php
function renderStudySpaceLoader(string $id = 'studyspace-loader'): string
{
    $safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

    return <<<HTML
  <div id="{$safeId}" class="loader-overlay" role="status" aria-live="polite" aria-label="Opening up the library">
    <div class="loader-panel">
      <div class="ss-loader-box" aria-hidden="true">
        <div class="ss-book ss-blue"></div>
        <div class="ss-book ss-brown-1"></div>
        <div class="ss-book ss-brown-2"></div>
        <div class="ss-book ss-brown-3"></div>
      </div>
      <p class="loader-message">opening up the library<span class="loader-ellipsis" aria-hidden="true"> . . .</span></p>
    </div>
  </div>
HTML;
}
