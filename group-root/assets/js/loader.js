class StudySpaceLoader {
    constructor(elementId = 'mainLoader') {
        this.loader = document.getElementById(elementId);
    }

    show() {
        if (this.loader) {
            this.loader.classList.remove('is-hidden');
        }
    }

    hide() {
        if (this.loader) {
            this.loader.classList.add('is-hidden');
        }
    }
}

// Auto-hide once page finishes loading
window.addEventListener('load', () => {
    const loader = new StudySpaceLoader('mainLoader');
    setTimeout(() => loader.hide(), 1200);

    // Demo replay button
    const replayBtn = document.getElementById('toggleLoader');
    if (replayBtn) {
        replayBtn.addEventListener('click', () => {
            loader.show();
            setTimeout(() => loader.hide(), 1200);
        });
    }
});