(() => {
    const painel = document.querySelector('.chat-aplicacao');
    const botoesContatos = Array.from(document.querySelectorAll('[data-chat-contato]'));
    const destinatario = document.querySelector('#chat-destinatario');
    const lista = document.querySelector('#chat-mensagens');
    const formulario = document.querySelector('#chat-form');
    const campoMensagem = document.querySelector('#chat-texto');
    const status = document.querySelector('#chat-status');
    const nomeContato = document.querySelector('#chat-nome-contato');
    const tipoContato = document.querySelector('#chat-tipo-contato');
    const avatarContato = document.querySelector('.chat-conversa-cabecalho .chat-contato-avatar');

    if (!painel || !destinatario || !lista || !formulario || !campoMensagem || !status || !nomeContato || !tipoContato || !avatarContato) {
        return;
    }

    const api = painel.dataset.chatApi;
    const usuarioId = Number(painel.dataset.chatUsuario);
    const csrf = painel.dataset.chatCsrf;
    let consultaEmAndamento = false;
    let envioEmAndamento = false;
    let versaoContato = 0;

    const mostrarStatus = (texto) => {
        status.textContent = texto;
    };

    const formatarData = (valor) => {
        const data = new Date(valor.replace(' ', 'T'));
        return Number.isNaN(data.getTime())
            ? valor
            : data.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
    };

    const desenharMensagem = (mensagem) => {
        const propria = Number(mensagem.remetente_id) === usuarioId;
        const item = document.createElement('article');
        item.className = propria
            ? 'chat-mensagem chat-mensagem-propria'
            : 'chat-mensagem chat-mensagem-recebida';

        const autor = document.createElement('strong');
        autor.className = 'chat-autor';
        autor.textContent = propria ? 'Você' : mensagem.remetente_nome;

        const texto = document.createElement('p');
        texto.className = 'mb-1';
        texto.textContent = mensagem.mensagem;

        const rodape = document.createElement('div');
        rodape.className = 'chat-rodape-mensagem';

        const horario = document.createElement('time');
        horario.className = 'chat-horario';
        horario.dateTime = mensagem.criado_em;
        horario.textContent = formatarData(mensagem.criado_em);
        rodape.append(horario);

        if (propria) {
            const leitura = document.createElement('span');
            leitura.className = 'chat-leitura';
            leitura.textContent = Number(mensagem.visualizada) === 1 ? 'Lida' : 'Enviada';
            rodape.append(leitura);
        }

        item.append(autor, texto, rodape);
        lista.append(item);
    };

    const atualizar = async () => {
        const destinatarioId = Number(destinatario.value);
        if (!destinatarioId || consultaEmAndamento) {
            return;
        }

        consultaEmAndamento = true;
        const versaoConsulta = versaoContato;
        try {
            const url = new URL(api, window.location.href);
            url.searchParams.set('destinatario_id', String(destinatarioId));
            const resposta = await fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const dados = await resposta.json();
            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível carregar as mensagens.');
            }
            if (versaoConsulta !== versaoContato) {
                return;
            }

            lista.replaceChildren();
            for (const mensagem of dados.mensagens) {
                desenharMensagem(mensagem);
            }
            lista.scrollTop = lista.scrollHeight;
            mostrarStatus('');
        } catch (erro) {
            if (versaoConsulta === versaoContato) {
                mostrarStatus(erro.message);
            }
        } finally {
            consultaEmAndamento = false;
            if (versaoConsulta !== versaoContato) {
                void atualizar();
            }
        }
    };

    for (const botaoContato of botoesContatos) {
        botaoContato.addEventListener('click', () => {
            const id = Number(botaoContato.dataset.chatContato);
            if (!id || id === Number(destinatario.value)) {
                return;
            }

            versaoContato += 1;
            destinatario.value = String(id);
            nomeContato.textContent = botaoContato.dataset.chatNome;
            tipoContato.textContent = botaoContato.dataset.chatTipo;
            avatarContato.textContent = botaoContato.dataset.chatNome.trim().charAt(0).toUpperCase();
            for (const botao of botoesContatos) {
                const ativo = botao === botaoContato;
                botao.classList.toggle('ativo', ativo);
                botao.setAttribute('aria-pressed', String(ativo));
            }
            lista.replaceChildren();
            void atualizar();
        });
    }

    campoMensagem.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter' && !evento.shiftKey && !evento.isComposing) {
            evento.preventDefault();
            formulario.requestSubmit();
        }
    });

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (envioEmAndamento) {
            return;
        }

        const mensagem = campoMensagem.value.trim();
        const destinatarioId = Number(destinatario.value);
        if (!mensagem || !destinatarioId) {
            return;
        }

        const botao = formulario.querySelector('button[type="submit"]');
        envioEmAndamento = true;
        botao.disabled = true;
        campoMensagem.value = '';
        mostrarStatus('Enviando...');

        try {
            const resposta = await fetch(api, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-Token': csrf,
                },
                body: JSON.stringify({ destinatario_id: destinatarioId, mensagem }),
            });
            const dados = await resposta.json();
            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível enviar a mensagem.');
            }

            mostrarStatus('');
            await atualizar();
            campoMensagem.focus();
        } catch (erro) {
            if (campoMensagem.value === '') {
                campoMensagem.value = mensagem;
            }
            mostrarStatus(erro.message);
        } finally {
            envioEmAndamento = false;
            botao.disabled = false;
        }
    });

    void atualizar();
    window.setInterval(() => void atualizar(), 2000);
})();
