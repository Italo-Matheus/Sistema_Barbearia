<?php if (!empty($chatContatos)): ?>
    <?php $chatDestinatarioInicial = (int) $chatContatos[0]['id']; ?>
    <section class="chat-aplicacao"
             data-chat-api="<?= e(baseAplicacao() . 'api/chat.php') ?>"
             data-chat-usuario="<?= (int) $chatUsuarioId ?>"
             data-chat-csrf="<?= e(tokenCsrf()) ?>">
        <aside class="chat-contatos" aria-label="Contatos">
            <header class="chat-contatos-cabecalho">
                <h2 class="h5 mb-1">Conversas</h2>
                <p class="small mb-0">Seus contatos</p>
            </header>
            <nav class="chat-contatos-lista" aria-label="Lista de conversas">
                <?php foreach ($chatContatos as $indice => $contato): ?>
                    <button class="chat-contato<?= $indice === 0 ? ' ativo' : '' ?>" type="button"
                        data-chat-contato="<?= (int) $contato['id'] ?>"
                        data-chat-nome="<?= e($contato['nome']) ?>"
                        data-chat-tipo="<?= e(ucfirst((string) $contato['tipo'])) ?>"
                        aria-pressed="<?= $indice === 0 ? 'true' : 'false' ?>">
                    <span class="chat-contato-avatar" aria-hidden="true">
                        <?= e(strtoupper(substr((string) $contato['nome'], 0, 1))) ?>
                    </span>
                        <span class="chat-contato-dados">
                            <strong><?= e($contato['nome']) ?></strong>
                            <small><?= e(ucfirst((string) $contato['tipo'])) ?></small>
                        </span>
                    </button>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="chat-conversa" id="chat-conversa">
            <header class="chat-conversa-cabecalho">
                <span class="chat-contato-avatar" aria-hidden="true">
                    <?= e(strtoupper(substr((string) $chatContatos[0]['nome'], 0, 1))) ?>
                </span>
                <div>
                    <h2 class="h5 mb-0" id="chat-nome-contato"><?= e($chatContatos[0]['nome']) ?></h2>
                    <small class="texto-suave" id="chat-tipo-contato"><?= e(ucfirst((string) $chatContatos[0]['tipo'])) ?></small>
                </div>
            </header>
            <input type="hidden" id="chat-destinatario" value="<?= $chatDestinatarioInicial ?>">
            <div class="chat-mensagens" id="chat-mensagens" role="log"
                 aria-live="polite" aria-relevant="additions"></div>
            <p class="small texto-suave chat-status" id="chat-status" aria-live="polite"></p>
            <form id="chat-form" class="chat-form">
                <label class="visually-hidden" for="chat-texto">Mensagem</label>
                <textarea class="form-control" id="chat-texto" rows="2" maxlength="2000"
                          placeholder="Escreva sua mensagem..." required></textarea>
                <button class="btn btn-marrom" type="submit" aria-label="Enviar mensagem">
                    <i class="bi bi-send-fill me-sm-1" aria-hidden="true"></i><span>Enviar</span>
                </button>
            </form>
            <p class="form-text chat-ajuda">Atualização a cada 2 segundos · Enter envia · Shift+Enter quebra linha</p>
        </div>
    </section>
<?php else: ?>
    <section class="cartao vazio" aria-labelledby="chat-titulo">
        <i class="bi bi-chat-dots display-5 texto-suave" aria-hidden="true"></i>
        <h2 class="h4 mt-3 mb-2" id="chat-titulo">Nenhuma conversa disponível</h2>
        <p class="texto-suave mb-0">
            Seus contatos ativos aparecerão aqui quando estiverem disponíveis para conversar.
        </p>
    </section>
<?php endif; ?>
