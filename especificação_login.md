**Fase 1: Estrutura de Banco de Dados e Migrations**

**1. Atualização do `migration.php**`
Adicionar duas novas tabelas ao script de migração:

* `users`: Campos `id` (VARCHAR 50), `name` (VARCHAR 255), `email` (VARCHAR 255, UNIQUE), `password_hash` (VARCHAR 255), `role` (VARCHAR 50, DEFAULT 'common'), `created_at` (DATETIME).
* `user_sessions`: Campos `id` (VARCHAR 50), `user_id` (VARCHAR 50, FOREIGN KEY para users), `refresh_token` (VARCHAR 255), `user_agent` (TEXT), `ip_address` (VARCHAR 45), `expires_at` (DATETIME), `created_at` (DATETIME). Esta tabela garantirá o controle de acessos em dispositivos diferentes, permitindo que o usuário tenha múltiplas sessões ativas (celular, PC) e possa deslogar de um dispositivo específico ou de todos.

**2. Seeder de Usuário Padrão**
No próprio `migration.php`, logo após a criação das tabelas, implementar uma rotina que verifica se a tabela `users` está vazia (usando um `SELECT COUNT(*)` no MySQL ou checando o tamanho do array no JSON). Se estiver vazia, inserir:

* `id`: gerado via `uniqid()`
* `name`: 'Administrador'
* `email`: 'admin@effecta.com'
* `password_hash`: gerado com `password_hash('admin123', PASSWORD_BCRYPT)`
* `role`: 'admin'

**3. Atualização do `src/EffectaORM.php**`
O ORM atual precisa de novos métodos para suportar a autenticação:

* `getBy(table, column, value)`: Para buscar um usuário pelo e-mail ou token.
* `update(table, id, data)`: Para atualizar dados, como senhas.
* `delete(table, column, value)`: Para remover sessões (logout).

**Fase 2: Sistema de Tokens e API (`api/index.php`)**

**1. Geração de Tokens (JWT)**
Utilizar a biblioteca `firebase/php-jwt` via Composer para assinar os tokens, garantindo que não sejam forjados. O payload do Access Token deve conter `user_id`, `role` e `exp` (timestamp atual + 15 minutos). O payload do Refresh Token deve conter um identificador único da sessão (JTI), `user_id` e `exp` (timestamp atual + 7 dias).

**2. Endpoints Necessários (`POST`)**

* `login`: Recebe `email` e `password`. Busca o usuário via `getBy`. Verifica a senha com `password_verify`. Gera o Access Token e o Refresh Token. Insere um registro na tabela `user_sessions` com o `refresh_token` criptografado (ou hash), o `user_agent` da requisição e a data de expiração. Retorna ambos os tokens.
* `refresh`: Recebe o `refresh_token` atual. Busca na tabela `user_sessions` pelo token. Verifica se não está expirado e se a assinatura do JWT é válida. Se válido, gera um novo Access Token (15 min) e um novo Refresh Token (7 dias). Atualiza a linha correspondente em `user_sessions` com o novo token e expiração. Retorna os novos tokens.
* `logout`: Recebe o `refresh_token`. Remove a linha correspondente na tabela `user_sessions`. Isso invalida a sessão daquele dispositivo específico sem afetar outros dispositivos logados.

**Fase 3: Controle de Rotas Públicas e Privadas (`index.php`)**

**1. Middleware de Roteamento**
No `index.php`, criar um array `$publicPages = ['login', 'recuperar-senha']`.
Antes de carregar o `$pageFile`, verificar se a variável `$page` está no array de páginas públicas.

**2. Validação de Acesso**
Se a página não for pública, exigir a presença de um cookie contendo o Access Token ou fazer uma validação de sessão em PHP puro caso decida gerenciar os tokens via `HttpOnly` Cookies.
Caso o token não exista ou seja inválido (expirado ou assinatura incorreta), forçar o redirecionamento: `header('Location: ?page=login'); exit;`.

**3. Controle de Permissões (RBAC)**
Para as páginas restritas a administradores (ex: `usuarios`), ler o payload decodificado do Access Token. Se o `role` for diferente de `admin`, exibir uma página de erro 403 (Acesso Negado) ou redirecionar para a dashboard comum. A estrutura usando uma coluna de string `role` permite a adição de valores como 'manager' ou 'viewer' no futuro sem alterar o banco de dados.

**Fase 4: Frontend e Interceptação (`assets/js/api.js`)**

**1. Armazenamento Seguro**
Salvar o Access Token em memória (variável no JS) e o Refresh Token no `localStorage` (ou idealmente, o backend deve enviar o Refresh Token como um cookie `HttpOnly` para evitar XSS).

**2. Interceptador de Requisições**
Modificar o objeto `EffectaAPI` para incluir o Access Token no header `Authorization: Bearer <token>` de todas as chamadas `fetch`.
Adicionar um bloco `try/catch` genérico nas chamadas. Se a API retornar HTTP 401 (Unauthorized), a aplicação deve:

* Pausar a requisição original.
* Fazer uma chamada automática para o endpoint `refresh` enviando o Refresh Token.
* Se o refresh falhar (Refresh Token expirado), limpar o armazenamento e redirecionar para a tela de login.
* Se o refresh der sucesso, atualizar o Access Token em memória e refazer a requisição original que havia falhado, retornando o resultado transparente para o usuário.

**Fase 5: Login com o Google**

**1. Configuração no Google Cloud Console**
Criar credenciais OAuth 2.0. Configurar as URIs de redirecionamento e as origens JavaScript autorizadas. Obter o `Client ID`.

**2. Frontend (`pages/login.php`)**
Importar o script do Google Identity Services: `<script src="https://accounts.google.com/gsi/client" async defer></script>`.
Renderizar o botão do Google. Quando o usuário clica e autoriza, o Google retorna um JWT (Credential) diretamente para a função de callback no JavaScript.

**3. Backend (`api/index.php` -> `action=google_login`)**
Receber o JWT do Google via `POST`.
Validar a assinatura do token usando a biblioteca oficial do Google para PHP (`google/apiclient`).
Após a validação, extrair o `email` e `name` do payload do token do Google.
Consultar o ORM: `getBy('users', 'email', $email)`.

* Se o usuário existir: Segue o fluxo normal (gera Access/Refresh Tokens, salva em `user_sessions`, retorna os tokens).
* Se o usuário não existir: Insere um novo usuário na tabela `users` com o e-mail, nome, senha vazia (ou hash aleatório) e `role` definida como 'common'. Em seguida, loga o usuário gerando e retornando os tokens.