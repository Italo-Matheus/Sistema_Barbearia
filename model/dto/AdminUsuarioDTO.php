<?php

class AdminUsuarioDTO
{
    public function __construct(
        public int $id,
        public string $nome,
        public string $email,
        public string $telefone,
        public ?string $cpf,
        public string $tipo,
        public ?string $especialidade,
        public ?string $descricao
    ) {
    }
}