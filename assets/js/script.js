(() => {
    'use strict';

    document.querySelectorAll('input[name="senha"], input[name="senha_nova"]').forEach((password) => {
        const form = password.form;
        const confirmation = form?.querySelector('input[name="confirmar_senha"]');
        if (!confirmation) {
            return;
        }

        const helpTexts = Array.from(password.parentElement.querySelectorAll('.form-text'));
        let label = helpTexts.shift();
        if (!label) {
            label = document.createElement('div');
            label.className = 'form-text';
            password.parentElement.append(label);
        }
        helpTexts.forEach((helpText) => helpText.remove());
        label.classList.add('d-block');
        label.setAttribute('aria-live', 'polite');

        const container = document.createElement('div');
        container.className = 'forca-senha';

        const meter = document.createElement('span');
        meter.className = 'forca-senha-trilho';
        meter.setAttribute('role', 'meter');
        meter.setAttribute('aria-label', 'Força da senha');
        meter.setAttribute('aria-valuemin', '0');
        meter.setAttribute('aria-valuemax', '5');

        const bar = document.createElement('span');
        bar.className = 'forca-senha-barra';
        meter.append(bar);
        container.append(meter);
        label.insertAdjacentElement('afterend', container);

        const update = () => {
            const value = password.value;
            const checks = [
                value.length >= 8,
                /[a-z]/.test(value),
                /[A-Z]/.test(value),
                /\d/.test(value),
                /[^A-Za-z0-9]/.test(value)
            ];
            const score = checks.filter(Boolean).length;
            const strength = value.length === 0
                ? 'Informe uma senha com 8 a 72 caracteres.'
                : value.length < 8 || score < 3
                    ? 'Senha fraca. Use 8 a 72 caracteres e combine letras e números.'
                    : score < 4
                        ? 'Senha razoável. Adicione maiúsculas, números ou símbolos (até 72 caracteres).'
                        : 'Senha forte (8 a 72 caracteres).';

            label.textContent = strength;
            meter.setAttribute('aria-valuenow', String(score));
            meter.setAttribute('aria-valuetext', strength);
            bar.style.transform = `scaleX(${score / 5})`;
            bar.dataset.forca = score < 3 ? 'fraca' : score < 4 ? 'razoavel' : 'forte';

            confirmation.setCustomValidity(
                confirmation.value && confirmation.value !== value
                    ? 'As senhas não coincidem.'
                    : ''
            );
        };

        password.addEventListener('input', update);
        confirmation.addEventListener('input', update);
        update();
    });
})();
