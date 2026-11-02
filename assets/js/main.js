(() => {
    'use strict';

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
    const digitsOnly = (value) => String(value || '').replace(/\D/g, '');

    function formatPhone(value) {
        let digits = digitsOnly(value);
        if (digits.length > 11 && digits.startsWith('55')) {
            digits = digits.slice(2);
        }
        digits = digits.slice(0, 11);

        if (digits.length <= 2) {
            return digits ? `(${digits}` : '';
        }

        const ddd = digits.slice(0, 2);
        const local = digits.slice(2);
        const split = digits.length > 10 ? 5 : 4;
        return `(${ddd}) ${local.slice(0, split)}${local.length > split ? `-${local.slice(split)}` : ''}`;
    }

    function formatCpf(value) {
        const digits = digitsOnly(value).slice(0, 11);
        let formatted = digits.slice(0, 3);
        if (digits.length > 3) formatted += `.${digits.slice(3, 6)}`;
        if (digits.length > 6) formatted += `.${digits.slice(6, 9)}`;
        if (digits.length > 9) formatted += `-${digits.slice(9)}`;
        return formatted;
    }

    function applyMask(input, formatter) {
        const cursor = input.selectionStart ?? input.value.length;
        const digitsBeforeCursor = digitsOnly(input.value.slice(0, cursor)).length;
        const formatted = formatter(input.value);
        if (formatted === input.value) return;

        input.value = formatted;
        let position = 0;
        let digitsSeen = 0;
        while (position < formatted.length && digitsSeen < digitsBeforeCursor) {
            if (/\d/.test(formatted[position])) digitsSeen += 1;
            position += 1;
        }
        try {
            input.setSelectionRange(position, position);
        } catch {
            // Alguns tipos de campo não oferecem seleção de cursor.
        }
    }

    function initMasks() {
        $$('[data-mask="telefone"], input[type="tel"], input[name="telefone"]').forEach((input) => {
            input.value = formatPhone(input.value);
            input.addEventListener('input', () => applyMask(input, formatPhone));
        });
        $$('[data-mask="cpf"], input[name="cpf"]').forEach((input) => {
            input.value = formatCpf(input.value);
            input.addEventListener('input', () => applyMask(input, formatCpf));
        });
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    const rules = {
        login: {
            email: (value) => !value.trim() ? 'Informe o seu e-mail.' : emailPattern.test(value.trim()) ? '' : 'Esse e-mail não parece válido.',
            senha: (value) => value ? '' : 'Digite a sua senha.'
        },
        cadastro: {
            nome: (value) => value.trim().length < 5 || !value.trim().includes(' ') ? 'Informe nome e sobrenome.' : '',
            email: (value) => !value.trim() ? 'Informe o seu e-mail.' : emailPattern.test(value.trim()) ? '' : 'Esse e-mail não parece válido.',
            telefone: (value) => [10, 11].includes(digitsOnly(value).length) ? '' : 'Informe o telefone com DDD, por exemplo (61) 91234-5678.',
            cpf: (value) => !value || digitsOnly(value).length === 11 ? '' : 'O CPF deve ter 11 números.',
            senha: (value) => value.length < 8 ? 'Use uma senha com pelo menos 8 caracteres.' : value.length > 72 ? 'A senha pode ter até 72 caracteres.' : '',
            confirmar_senha: (value, form) => !value ? 'Confirme a sua senha.' : value !== form.elements.senha.value ? 'As senhas não são iguais.' : ''
        }
    };

    function clearError(field) {
        field.classList.remove('is-invalid');
        field.removeAttribute('aria-invalid');
        if (field.nextElementSibling?.classList.contains('invalid-feedback')) {
            field.nextElementSibling.remove();
        }
    }

    function showError(field, message) {
        clearError(field);
        field.classList.add('is-invalid', 'erro-sacudindo');
        field.setAttribute('aria-invalid', 'true');
        const error = document.createElement('div');
        error.className = 'invalid-feedback erro-feedback';
        error.textContent = message;
        field.insertAdjacentElement('afterend', error);
        window.setTimeout(() => field.classList.remove('erro-sacudindo'), 420);
    }

    function validateField(field, formRules, form) {
        const message = formRules[field.name](field.value, form);
        if (message) {
            showError(field, message);
            return false;
        }
        clearError(field);
        return true;
    }

    function initAuthForm(form) {
        const formRules = rules[form.dataset.validar];
        if (!formRules) return;

        const fields = $$('input', form).filter((field) => formRules[field.name]);
        const validate = (field) => validateField(field, formRules, form);
        form.noValidate = true;

        fields.forEach((field) => {
            field.addEventListener('blur', () => {
                if (field.value !== '') validate(field);
            });
            field.addEventListener('input', () => {
                if (field.classList.contains('is-invalid')) validate(field);
            });
        });

        form.addEventListener('submit', (event) => {
            const invalidFields = fields.filter((field) => !validate(field));
            if (invalidFields.length) {
                event.preventDefault();
                invalidFields[0].focus();
            }
        });
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value);
    }

    function animateTotal(total, nextValue) {
        const currentValue = Number.parseFloat(total.textContent.replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reducedMotion || currentValue === nextValue) {
            total.textContent = formatCurrency(nextValue);
            return;
        }

        const startedAt = performance.now();
        const duration = 220;
        const tick = (now) => {
            const progress = Math.min((now - startedAt) / duration, 1);
            total.textContent = formatCurrency(currentValue + (nextValue - currentValue) * progress);
            if (progress < 1) {
                requestAnimationFrame(tick);
            } else {
                total.textContent = formatCurrency(nextValue);
            }
        };
        total.classList.remove('total-atualizado');
        requestAnimationFrame(() => total.classList.add('total-atualizado'));
        requestAnimationFrame(tick);
    }

    function initBooking() {
        const dateField = $('#data.campo-data');
        if (!dateField) return;

        const form = dateField.form;
        const today = new Date();
        const localToday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        if (!dateField.min || dateField.min < localToday) {
            dateField.min = localToday;
        }

        const summary = $('#resumo-servicos');
        const total = $('#resumo-total');
        const status = $('#status-disponibilidade');

        function updateSummary() {
            if (!summary || !total) return;
            let sum = 0;
            const rows = $$('input[name="servicos[]"]:checked', form).map((checkbox) => {
                const price = Number.parseFloat(checkbox.dataset.preco) || 0;
                sum += price;
                const row = document.createElement('div');
                row.className = 'resumo-linha';
                row.classList.add('resumo-linha-atualizada');
                const name = document.createElement('span');
                name.textContent = checkbox.dataset.nome || checkbox.value;
                const value = document.createElement('span');
                value.textContent = formatCurrency(price);
                row.append(name, value);
                return row;
            });

            if (rows.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'texto-suave mb-0';
                empty.textContent = 'Os serviços que você escolher aparecem aqui.';
                rows.push(empty);
            }
            summary.replaceChildren(...rows);
            animateTotal(total, sum);
        }

        function updateStatus() {
            if (!status) return;
            const selectedServices = $$('input[name="servicos[]"]:checked', form).length;
            const barberSelected = $('input[name="barbeiro_id"]:checked', form);
            status.textContent = selectedServices && barberSelected && dateField.value
                ? 'Seleções atualizadas. Clique em “Ver horários livres” para consultar a disponibilidade.'
                : 'Selecione serviços, barbeiro e data para consultar os horários livres.';
        }

        dateField.addEventListener('change', () => {
            const invalid = dateField.value && (
                dateField.value < dateField.min ||
                (dateField.max && dateField.value > dateField.max)
            );
            dateField.setCustomValidity(invalid ? 'Escolha uma data dentro do período disponível.' : '');
            if (invalid) {
                dateField.reportValidity();
                dateField.value = '';
            }
            updateStatus();
        });

        form.addEventListener('change', (event) => {
            if (event.target.matches('input[name="servicos[]"]')) {
                updateSummary();
            }
            if (event.target.matches('input[name="servicos[]"], input[name="barbeiro_id"], #data, input[name="horario"]')) {
                updateStatus();
            }
            if (event.target.matches('input[name="horario"]')) {
                const destination = $('#resumo-horario');
                if (destination && event.target.checked) destination.textContent = event.target.value;
            }
        });

        form.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            if (!submitter || submitter.name !== 'acao' || submitter.value !== 'ver_horarios') return;
            if (status) {
                status.textContent = 'Carregando horários…';
                status.classList.add('carregando-horarios');
            }
        });

        updateSummary();
        updateStatus();
    }

    function init() {
        initMasks();
        $$('form[data-validar]').forEach(initAuthForm);
        initBooking();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
