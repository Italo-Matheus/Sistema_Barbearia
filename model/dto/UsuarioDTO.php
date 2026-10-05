<?php

class UsuarioDTO
{
    public const TIPOS_VALIDOS = ['cliente', 'barbeiro', 'admin'];

    public function __construct(
        public string $nome,
        public string $email,
        public string $telefone,
        public ?string $cpf,
        public string $senhaHash,
        public string $tipo = 'cliente',
        public ?string $especialidade = null,
        public ?string $descricao = null,
        public bool $ativo = true
    ) {
    }
}