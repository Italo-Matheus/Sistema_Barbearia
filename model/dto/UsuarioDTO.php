<?php

class UsuarioDTO
{
    public function __construct(
        public string $nome,
        public string $email,
        public string $telefone,
        public ?string $cpf,
        public string $senhaHash,
        public string $tipo = 'cliente',
        public ?string $especialidade = null,
        public ?string $descricao = null
    ) {
    }
}