(() => {
    'use strict';

    const pending = new WeakSet();
    const approvedSubmitters = new WeakSet();
    const approvedClicks = new WeakSet();
    const processingButtons = new WeakSet();
    let activeConfirmation = null;
    let confirmationDialog;
    let confirmationTitle;
    let confirmationMessage;
    let confirmationIcon;
    let cancelButton;
    let confirmButton;

    function createElement(tag, className, text) {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (text !== undefined) {
            element.textContent = text;
        }
        return element;
    }

    function createDialog() {
        if (confirmationDialog) {
            return confirmationDialog;
        }

        confirmationDialog = createElement('dialog', 'confirmacao-modal');
        confirmationDialog.setAttribute('aria-modal', 'true');
        confirmationDialog.setAttribute('aria-labelledby', 'confirmacao-titulo');
        confirmationDialog.setAttribute('aria-describedby', 'confirmacao-mensagem');

        const content = createElement('div', 'confirmacao-conteudo');
        const heading = createElement('div', 'confirmacao-cabecalho');
        confirmationIcon = createElement('i', 'confirmacao-icone bi bi-question-circle');
        confirmationIcon.setAttribute('aria-hidden', 'true');
        confirmationTitle = createElement('h2', 'confirmacao-titulo', 'Tem certeza?');
        confirmationTitle.id = 'confirmacao-titulo';
        heading.append(confirmationIcon, confirmationTitle);

        confirmationMessage = createElement('p', 'confirmacao-mensagem');
        confirmationMessage.id = 'confirmacao-mensagem';

        const actions = createElement('div', 'confirmacao-acoes');
        cancelButton = createElement('button', 'btn btn-contorno', 'Voltar');
        cancelButton.type = 'button';
        confirmButton = createElement('button', 'btn btn-marrom', 'Confirmar');
        confirmButton.type = 'button';
        actions.append(cancelButton, confirmButton);
        content.append(heading, confirmationMessage, actions);
        confirmationDialog.append(content);
        document.body.append(confirmationDialog);

        cancelButton.addEventListener('click', () => finishConfirmation(false));
        confirmButton.addEventListener('click', () => finishConfirmation(true));
        confirmationDialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            finishConfirmation(false);
        });
        confirmationDialog.addEventListener('click', (event) => {
            if (event.target === confirmationDialog) {
                finishConfirmation(false);
            }
        });
        confirmationDialog.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                finishConfirmation(false);
                return;
            }
            if (event.key !== 'Tab') {
                return;
            }

            const focusable = [cancelButton, confirmButton];
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        return confirmationDialog;
    }

    function getConfirmationMessage(control) {
        const message = control.dataset.confirmar || '';
        if (control instanceof HTMLButtonElement && control.name === 'acao' && control.value === 'confirmar') {
            const details = control.form?.querySelectorAll('.resumo-detalhes dd');
            if (details?.length >= 3) {
                const barber = details[0].textContent.trim();
                const date = details[1].textContent.trim();
                const time = details[2].textContent.trim();
                return `Confirma o agendamento com ${barber} para ${date}, às ${time}?`;
            }
        }
        return message;
    }

    function confirmar(control) {
        if (activeConfirmation || pending.has(control)) {
            return Promise.resolve(false);
        }

        const dialog = createDialog();
        const type = ['perigo', 'aviso', 'info'].includes(control.dataset.confirmarTipo)
            ? control.dataset.confirmarTipo
            : 'aviso';
        const icons = {
            perigo: 'bi-exclamation-octagon-fill',
            aviso: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };
        const title = control.dataset.confirmarTitulo || 'Tem certeza?';
        const confirmLabel = control.dataset.confirmarBotao || 'Confirmar';
        const message = getConfirmationMessage(control);

        confirmationDialog.classList.remove('tipo-perigo', 'tipo-aviso', 'tipo-info');
        confirmationDialog.classList.add(`tipo-${type}`);
        confirmationTitle.textContent = title;
        confirmationMessage.textContent = message;
        confirmationIcon.className = `confirmacao-icone bi ${icons[type]}`;
        confirmButton.textContent = confirmLabel;
        confirmButton.classList.toggle('btn-marrom', type !== 'perigo');
        confirmButton.classList.toggle('btn-danger', type === 'perigo');
        activeConfirmation = { control, resolve: null };
        pending.add(control);

        const result = new Promise((resolve) => {
            activeConfirmation.resolve = resolve;
        });

        dialog.showModal();
        cancelButton.focus();
        return result;
    }

    function finishConfirmation(confirmed) {
        if (!activeConfirmation) {
            return;
        }

        const current = activeConfirmation;
        activeConfirmation = null;
        pending.delete(current.control);
        confirmationDialog.close();
        current.control.focus({ preventScroll: true });
        current.resolve(confirmed);
    }

    function setProcessing(button) {
        if (!(button instanceof HTMLButtonElement) || processingButtons.has(button)) {
            return;
        }

        processingButtons.add(button);
        button.setAttribute('aria-disabled', 'true');
        button.setAttribute('aria-busy', 'true');
        button.classList.add('is-processing');
        button.replaceChildren(
            createElement('span', 'spinner-border spinner-border-sm me-2'),
            createElement('span', '', 'Processando…')
        );
        button.querySelector('.spinner-border').setAttribute('aria-hidden', 'true');

        // Desabilita após o navegador coletar o name/value do submitter.
        window.setTimeout(() => {
            button.disabled = true;
        }, 0);
    }

    function submitConfirmed(control) {
        if (control instanceof HTMLAnchorElement) {
            window.location.assign(control.href);
            return;
        }

        const form = control.form;
        if (!form) {
            approvedClicks.add(control);
            control.click();
            return;
        }

        if (!form.noValidate && !control.formNoValidate && !form.checkValidity()) {
            form.reportValidity();
            return;
        }

        approvedSubmitters.add(control);
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(control);
        } else {
            setProcessing(control);
            form.submit();
        }
    }

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        const control = target?.closest('button[data-confirmar], a[data-confirmar]');
        if (!control) {
            return;
        }
        if (approvedClicks.has(control)) {
            approvedClicks.delete(control);
            return;
        }
        if (control.matches(':disabled') || control.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
            return;
        }

        if (
            control instanceof HTMLButtonElement &&
            control.type === 'submit' &&
            control.form &&
            !control.form.noValidate &&
            !control.formNoValidate &&
            !control.form.checkValidity()
        ) {
            event.preventDefault();
            control.form.reportValidity();
            return;
        }

        event.preventDefault();
        if (pending.has(control)) {
            return;
        }

        confirmar(control).then((confirmed) => {
            if (confirmed) {
                submitConfirmed(control);
            }
        });
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        if (event.defaultPrevented) {
            return;
        }

        const submitter = event.submitter;
        if (submitter && approvedSubmitters.has(submitter)) {
            approvedSubmitters.delete(submitter);
            setProcessing(submitter);
            return;
        }

        if (submitter?.matches('button[data-confirmar]')) {
            event.preventDefault();
            if (pending.has(submitter)) {
                return;
            }
            confirmar(submitter).then((confirmed) => {
                if (confirmed) {
                    submitConfirmed(submitter);
                }
            });
            return;
        }

        if (submitter && submitter.name === 'acao' && submitter.value === 'ver_horarios') {
            return;
        }
        if (form.dataset.semSpinner === 'true') {
            return;
        }
        setProcessing(submitter || form.querySelector('button[type="submit"], input[type="submit"]'));
    });

    function revealElements() {
        const targets = [
            '.hero',
            '#servicos',
            '#como-funciona',
            '.chamada',
            '.item-servico',
            '.agendamento',
            '[aria-label="Métricas do sistema"] article',
            '.tabela-agenda tbody tr'
        ];
        const elements = Array.from(document.querySelectorAll(targets.join(',')));

        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const siblingCounts = new Map();
        elements.forEach((element) => {
            const group = element.parentElement;
            const index = siblingCounts.get(group) || 0;
            siblingCounts.set(group, index + 1);
            element.style.setProperty('--atraso-revelar', `${Math.min(index, 8) * 60}ms`);
            element.classList.add('revelar');
        });

        const observer = new IntersectionObserver((entries, currentObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revelado');
                    currentObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        elements.forEach((element) => observer.observe(element));
    }

    function initAlerts() {
        document.querySelectorAll('.alert').forEach((alert) => {
            alert.setAttribute('aria-live', 'polite');
            alert.classList.add('alert-entrando');

            const progress = createElement('span', 'alerta-progresso');
            progress.setAttribute('aria-hidden', 'true');
            alert.append(progress);

            let remaining = 6000;
            let startedAt = Date.now();
            let timeout;

            const pause = () => {
                window.clearTimeout(timeout);
                remaining = Math.max(0, remaining - (Date.now() - startedAt));
                alert.classList.add('alerta-pausado');
            };
            const resume = () => {
                if (
                    !alert.isConnected ||
                    alert.classList.contains('alerta-saindo') ||
                    alert.matches(':hover') ||
                    alert.contains(document.activeElement)
                ) {
                    return;
                }
                alert.classList.remove('alerta-pausado');
                startedAt = Date.now();
                timeout = window.setTimeout(() => {
                    alert.classList.add('alerta-saindo');
                    window.setTimeout(() => alert.remove(), 300);
                }, remaining);
            };

            alert.addEventListener('mouseenter', pause);
            alert.addEventListener('mouseleave', resume);
            alert.addEventListener('focusin', pause);
            alert.addEventListener('focusout', resume);
            resume();
        });
    }

    function initNavbar() {
        const navbar = document.querySelector('.navbar-barbearia');
        if (!navbar) {
            return;
        }

        const update = () => navbar.classList.toggle('rolou', window.scrollY > 8);
        update();
        window.addEventListener('scroll', update, { passive: true });
    }

    function initInvalidFeedback() {
        document.addEventListener('invalid', (event) => {
            const field = event.target;
            if (!(field instanceof HTMLElement)) {
                return;
            }
            field.classList.remove('erro-sacudindo');
            requestAnimationFrame(() => field.classList.add('erro-sacudindo'));
            window.setTimeout(() => field.classList.remove('erro-sacudindo'), 420);
        }, true);
    }

    function init() {
        revealElements();
        initAlerts();
        initNavbar();
        initInvalidFeedback();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.Barbearia = { confirmar };
})();
