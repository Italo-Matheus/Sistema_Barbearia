</main>

<footer class="rodape">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-md-6">
                <p class="rodape-marca mb-2">
                    <i class="bi bi-scissors" aria-hidden="true"></i> <?= e(NOME_BARBEARIA) ?>
                </p>
                <p class="mb-0 rodape-texto">Qualidade garantida! Venha e agende seu horário no conforto da sua casa.</p>
            </div>
            <div class="col-6 col-md-3">
                <p class="rodape-titulo">Navegue</p>
                <ul class="list-unstyled mb-0">
                    <li><a href="index.php">Início</a></li>
                    <li><a href="index.php#servicos">Serviços</a></li>
                    <li><a href="index.php#como-funciona">Como funciona</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-3">
                <p class="rodape-titulo">Sua conta</p>
                <ul class="list-unstyled mb-0">
                    <?php if (usuarioLogado()): ?>
                        <li><a href="<?= e(areaDoUsuario()) ?>"><?= tipoUsuario() === 'barbeiro' ? 'Minha agenda' : 'Meus horários' ?></a></li>
                    <?php else: ?>
                        <li><a href="login.php">Entrar</a></li>
                        <li><a href="cadastro.php">Criar conta</a></li>
                    <?php endif; ?>
                    <li><a href="agendamento.php">Agendar horário</a></li>
                </ul>
            </div>
        </div>
        <p class="rodape-copia mb-0">&copy; <?= date('Y') ?> <?= e(NOME_BARBEARIA) ?>. Todos os direitos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
