(() => {
    'use strict';

    const summaryTime = document.querySelector('#resumo-horario');
    if (!summaryTime) {
        return;
    }

    summaryTime.setAttribute('aria-live', 'polite');
    document.querySelectorAll('input[name="horario"]').forEach((option) => {
        option.addEventListener('change', () => {
            if (option.checked) {
                summaryTime.textContent = option.value;
            }
        });
    });
})();
