# Effecta - Seu Tracker de Impacto ⚡

O **Effecta** é um rastreador de impacto e esforço de progresso, desenvolvido sob uma arquitetura de Front Controller em PHP, alimentado por uma interface do usuário (UI/UX) moderna construída de forma modular em blocos Javascript e comunicação assíncrona (AJAX) em background (Zero-Refresh).

---

## 🚀 Como Iniciar o Projeto

### Pré-requisitos
- Servidor local rodando PHP 7.4 ou superior.

### Passo a Passo
1. Configure o seu banco de dados em `src/config/database.php` (veja instruções abaixo).
2. Execute a migration para estruturar os dados e inserir as contas de teste:
   ```bash
   php migration.php
   ```
3. Abra seu navegador e acesse: `http://localhost/effecta`

---

## 🔑 Contas de Teste Pré-Configuradas

Após executar o script `migration.php`, as seguintes contas de teste estarão disponíveis para uso na tela de login:

| Perfil | Email | Senha | Nível (Role) |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@effecta.com` | `admin123` | `admin` |
| **Usuário Comum** | `user@effecta.com` | `user123` | `common` |

---

## 💾 Configuração de Armazenamento (Banco de Dados)

O sistema suporta nativamente dois modos de armazenamento: **JSON** (banco de arquivos local) e **MySQL** (banco relacional).

A configuração é centralizada no arquivo `src/config/database.php`.

### Como configurar:
1. Navegue até a pasta `src/config/`.
2. Renomeie (ou crie uma cópia) do arquivo `database.example.php` para `database.php`.
3. Defina a chave `'storage_type'` como `'mysql'` (para usar banco MySQL) ou `'json'` (para usar sem banco).
4. Insira as credenciais do seu banco de dados MySQL (`host`, `dbname`, `user`, `password`).

---

## ⚡ Rodando as Migrações do Banco

Em vez de criar as tabelas manualmente, você pode executar o script de migração diretamente no terminal a partir do diretório raiz:

```bash
php migration.php
```

Esse script detectará as configurações de `database.php`. No caso do MySQL, criará o banco de dados caso não exista e gerará as tabelas estruturadas de forma automática, aplicando também a inserção dos usuários padrão.

---

## 🧪 Rodando os Testes Automatizados

O projeto vem equipado com uma suite de testes unitários executável via linha de comando (CLI) que valida as operações de escrita, leitura e busca (CRUD) do ORM, limpando o banco ao finalizar:

```bash
php testes.php
```

---

## 📊 Estrutura de Tabelas (SQL)

Para referência, a estrutura do banco de dados MySQL gerada pelas migrações é a seguinte:

```sql
-- Tabela de Pessoas/Autores
CREATE TABLE IF NOT EXISTS `people` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Projetos
CREATE TABLE IF NOT EXISTS `projects` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Registros de Progresso (registers)
CREATE TABLE IF NOT EXISTS `registers` (
  `id` VARCHAR(50) NOT NULL,
  `projeto` VARCHAR(255) NOT NULL,
  `atividade` VARCHAR(255) NOT NULL,
  `tipo_prazo` VARCHAR(20) NOT NULL,
  `horas_trabalhadas` DECIMAL(10,2) DEFAULT NULL,
  `prazo` DATE DEFAULT NULL,
  `data_entrega` DATE DEFAULT NULL,
  `horas_gastas` DECIMAL(10,2) DEFAULT NULL,
  `meta` TEXT DEFAULT NULL,
  `contribuicao` TEXT DEFAULT NULL,
  `impacto` TEXT DEFAULT NULL,
  `treinamentos` TEXT DEFAULT NULL,
  `stakeholders` TEXT DEFAULT NULL,
  `autor_feedback` VARCHAR(255) DEFAULT NULL,
  `feedbacks` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Usuários (users)
CREATE TABLE IF NOT EXISTS `users` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'common',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Sessões Ativas (user_sessions)
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` VARCHAR(50) NOT NULL,
  `user_id` VARCHAR(50) NOT NULL,
  `refresh_token` VARCHAR(255) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
