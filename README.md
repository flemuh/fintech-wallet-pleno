# Fintech Wallet

[![CI](https://github.com/flemuh/fintech-wallet-pleno/actions/workflows/ci.yml/badge.svg)](https://github.com/flemuh/fintech-wallet-pleno/actions/workflows/ci.yml)

Carteira digital pessoal com depósitos, saques e histórico de transações. API em Laravel 13 com Sanctum e SPA em Vue 3.

| | |
|---|---|
| **Aplicação no ar** | https://wallet.speedlora.app |
| **Repositório** | https://github.com/flemuh/fintech-wallet-pleno |
| **Usuário de teste** | `demo@wallet.speedlora.app` / `Demo@12345` |

O usuário de teste já tem saldo e movimentações dos últimos 30 dias. Para criar outra conta, use o link **Criar conta** na tela de login: nome, e-mail válido e senha com no mínimo 8 caracteres, letra maiúscula, letra minúscula, número e símbolo. A tela mostra a força da senha enquanto você digita, e a carteira nova começa com R$ 0,00.

## Sumário

- [Visão geral](#visão-geral)
- [Decisões técnicas](#decisões-técnicas)
- [Regras de negócio](#regras-de-negócio)
- [API](#api)
- [Segurança](#segurança)
- [Testes](#testes)
- [Pré-requisitos](#pré-requisitos)
- [Como rodar localmente](#como-rodar-localmente)
- [Deploy e CI/CD](#deploy-e-cicd)
- [Limitações conhecidas e próximos passos](#limitações-conhecidas-e-próximos-passos)

## Visão geral

- **Autenticação:** registro, login e logout com token Sanctum. Ao se registrar, o usuário recebe uma carteira com saldo R$ 0,00.
- **Operações:** depósito e saque na própria carteira, com valor positivo, até duas casas decimais e saldo suficiente no saque. Cada operação vira uma transação de crédito ou débito com o saldo após a operação.
- **Histórico:** listagem paginada com data e hora, tipo, valor e saldo após, com filtros por tipo e por período.
- **Painel:** saldo atual, total depositado e total sacado no mês atual e as 5 últimas transações.
- **Interface:** formulários com feedback de sucesso e erro, bloqueio do saque acima do saldo na própria tela e extrato com filtros e paginação guardados na URL.

```
fintech-wallet-pleno/
├── backend/              Laravel 13 (API)
├── frontend/             Vue 3 (SPA)
├── docker/               imagens de desenvolvimento e produção
├── deploy/deploy.sh      script de deploy na VPS
├── compose.yaml          ambiente de desenvolvimento
├── compose.prod.yaml     ambiente de produção
└── .github/workflows/    CI e deploy
```

## Decisões técnicas

**Camadas.** No backend, o Form Request valida, o controller só delega e devolve um Resource, e toda a regra de depósito e saque fica em `WalletService` (`depositar`, `sacar` e o método privado `aplicar`, que os dois compartilham). No frontend, a tela chama a store do Pinia, a store chama os módulos de `src/api`, e só `src/api` conhece o Axios.

**Sem interface, repository nem DTO.** Há uma única implementação do serviço, os testes usam o banco real e o Laravel resolve classes concretas. Eloquent bem usado basta para o escopo, como o próprio enunciado indica. No frontend, a pasta `src/api` já faz o papel de adaptador.

**Dinheiro em centavos.** O banco guarda valores em `BIGINT` de centavos, nunca em ponto flutuante. A API recebe `amount` em reais com até duas casas (`10.50`) e devolve tudo em centavos (`amount`, `balance`, `balance_after`); o frontend formata com `Intl.NumberFormat('pt-BR')`. Isso elimina erros de arredondamento, que são testados com casos como R$ 0,29, R$ 1,15 e R$ 57,55.

**Atomicidade e concorrência.** Depósito e saque rodam dentro de `DB::transaction` com `lockForUpdate` na carteira, e o banco tem `CHECK (balance >= 0)` e `CHECK (amount > 0)`. Se qualquer passo falhar, nada é gravado; um saque recusado não deixa registro no histórico.

**Idempotência em depósito e saque.** Se a rede cai depois que o servidor gravou a operação, mas antes de a resposta chegar, um reenvio criaria uma segunda operação. Como no Stripe, o cliente manda um cabeçalho `Idempotency-Key` (o frontend gera um UUID por operação e o repete no reenvio do mesmo valor). A chave é gravada na mesma transação da movimentação, com um hash do tipo e do valor: a mesma chave devolve a operação já feita, com o cabeçalho `Idempotent-Replayed: true`, e a mesma chave com outra operação é recusada (422 `idempotency_key_reused`). Como a carteira está travada, duas requisições simultâneas com a mesma chave passam uma de cada vez. Uma operação recusada não gasta a chave. O cabeçalho é opcional: sem ele, cada requisição é uma operação.

**Saldo após a operação gravado.** Cada transação guarda `balance_after` no momento em que é criada. O histórico não recalcula saldo, e as transações são imutáveis (sem rota de edição ou exclusão).

**Fuso do produto: horário de Brasília.** A API devolve datas em UTC no formato ISO 8601, o padrão de APIs públicas como as do GitHub e do Stripe, porque um instante em UTC não tem ambiguidade. As regras que dependem do dia, como o mês atual do painel e o período do filtro do extrato, seguem o horário de Brasília (`America/Sao_Paulo`), o fuso de referência do produto, como nos bancos brasileiros. A conversão acontece na consulta, e há testes para a virada do mês e para uma transação às 22h53, que em UTC já cai no dia seguinte. Na tela, os filtros de data usam dd/mm/aaaa em qualquer navegador, com um botão que abre o calendário do próprio navegador.

**PostgreSQL também nos testes.** O CI roda a suíte em PostgreSQL 16, o mesmo banco da produção, porque `lockForUpdate` e `CHECK` não se comportam igual no SQLite.

**Erros padronizados em português.** Toda resposta de erro traz `message` e `code`, e a validação acrescenta `errors` por campo. Toda rota em `/api` responde JSON, mesmo sem o cabeçalho `Accept`. Como a API não tem tela de login, `redirectGuestsTo(null)` faz uma requisição sem token receber 401 em JSON, em vez de um redirecionamento.

**Mesma origem em produção.** O Nginx serve o Vue em `/` e repassa só `/api` ao Laravel. No desenvolvimento, o proxy do Vite faz o mesmo, então o navegador nunca depende de CORS. O CORS restrito existe só como defesa extra.

**Interface sem biblioteca de componentes.** Tailwind CSS 4 e ícones do Lucide. Os componentes (campo, alerta, paginação, toast, medidor de senha, formulário de operação e tabela) são do próprio projeto. Depósito e saque usam o mesmo `OperationForm`, sem código duplicado. O campo de valor tem máscara de real (digitar `1050` vira `10,50`).

**Docker no desenvolvimento e na produção.** A mesma imagem do PHP serve os dois ambientes, com estágios diferentes.

## Regras de negócio

| Regra do enunciado | Como é garantida |
|---|---|
| Valor positivo e maior que zero | Form Request (`gt:0`), `CHECK (amount > 0)` no banco e exceção no serviço |
| Sem fração abaixo de R$ 0,01 | `decimal:0,2` na API; a tela só aceita duas casas |
| Saldo suficiente no saque | Conferido dentro da transação, com a carteira travada; 422 `insufficient_balance` |
| Toda operação vira transação | Saldo e transação gravados juntos no `WalletService` |
| Operação atômica | `DB::transaction` e `lockForUpdate`; falha desfaz tudo |
| Saldo inicial R$ 0,00 | Carteira criada no registro, na mesma transação do usuário |
| Saque bloqueado na tela | O formulário compara o valor com o saldo antes de chamar a API; o backend continua sendo a autoridade |

Limite por operação: R$ 999.999,99 (`max:999999.99`).

**Evidência de concorrência** (em produção, PHP-FPM):

| Cenário | Resultado |
|---|---|
| Saldo de R$ 100 e 10 saques simultâneos de R$ 50 | 2 respostas 201 e 8 respostas 422; saldo final 0 e só 2 saques gravados |
| 20 depósitos simultâneos de R$ 10 (desenvolvimento) | 20 respostas 201; saldo igual à soma das movimentações |

## API

Base: `/api`. Rotas da carteira exigem `Authorization: Bearer <token>`. Depósito e saque aceitam o cabeçalho opcional `Idempotency-Key` (até 100 caracteres: letras, números, `-` e `_`).

| Método | Rota | Sucesso | Erros |
|---|---|---|---|
| POST | `/auth/register` | 201 com usuário e token | 422, 429 |
| POST | `/auth/login` | 200 com usuário e token | 422, 429 |
| POST | `/auth/logout` | 204 | 401 |
| GET | `/wallet` | 200 com `balance`, `month_credit` e `month_debit` | 401, 429 |
| POST | `/wallet/deposits` | 201 com a transação | 401, 422, 429 |
| POST | `/wallet/withdrawals` | 201 com a transação | 401, 422, 429 |
| GET | `/wallet/transactions` | 200 paginado; filtros `type`, `from`, `to`, `per_page` (até 50) e `page` | 401, 422, 429 |
| GET | `/health` | 200 `{"status":"ok"}` | |

Exemplo de erro:

```json
{
  "message": "O campo valor deve ter no máximo duas casas decimais.",
  "code": "validation_failed",
  "errors": { "amount": ["O campo valor deve ter no máximo duas casas decimais."] }
}
```

Códigos: `validation_failed` (422), `insufficient_balance` (422), `unauthenticated` (401), `not_found` (404), `method_not_allowed` (405), `idempotency_key_reused` (422), `concurrent_operation` (409), `too_many_requests` (429) e `server_error` (500, sem detalhe interno quando `APP_DEBUG` está desligado). Datas saem em UTC no formato ISO 8601; a interface mostra no horário de Brasília.

## Segurança

- **Acesso só à própria carteira:** a API nunca recebe `wallet_id` nem `user_id`; usa sempre o dono do token.
- **Token:** expira em 24 horas (`SANCTUM_EXPIRATION`), tem o prefixo `fwallet_` para a varredura de segredos do GitHub reconhecer um vazamento e é revogado no logout.
- **Senha e e-mail:** no cadastro, e-mail validado (`email:rfc,filter`) e senha com 8+ caracteres, maiúscula, minúscula, número e símbolo. O login confere o formato do e-mail e o tamanho da senha (8 a 255 caracteres), mas não exige maiúscula, número ou símbolo, para não revelar a política nem bloquear contas antigas.
- **Limites de requisição:** registro 5 por minuto por IP; login 5 por minuto por IP e e-mail; depósito e saque 20 por minuto por usuário; demais rotas 60 por minuto. Na VPS, o Nginx ainda limita 10 requisições por segundo por IP.
- **IP real:** o Laravel confia só nos proxies internos (faixas privadas do Docker), então um `X-Forwarded-For` forjado não escapa dos limites. Há um teste que reproduz esse ataque.
- **CSRF:** não se aplica, porque o token vai no cabeçalho `Authorization`, que o navegador nunca envia sozinho. O modo de cookie do Sanctum está desligado.
- **Cabeçalhos:** HSTS, Content-Security-Policy (`default-src 'self'`), `X-Frame-Options: DENY` e `X-Content-Type-Options: nosniff`.
- **Cadeia de dependências:** `composer audit` e `npm audit` no CI; o npm instala sem scripts (`--ignore-scripts`) no CI, na imagem e no desenvolvimento.
- **Infraestrutura:** banco sem porta publicada, containers com limite de memória e CPU, deploy só com CI verde e aprovação manual, chave do CI restrita a executar o script de deploy.

**Trade-off assumido:** o token fica no `localStorage`, legível por JavaScript em caso de XSS. O cookie HttpOnly resolveria isso, mas traria o CSRF de volta e, para esconder o token de vez, exigiria um BFF, fora do escopo pedido. O risco é reduzido pela CSP restritiva, pelo Vue escapar todo texto renderizado (o projeto não usa `v-html`) e pela expiração do token.

## Testes

| Suíte | Quantidade | O que cobre |
|---|---|---|
| PHPUnit (backend) | 99 | Depósito, saque, saldo insuficiente, idempotência, valores inválidos e de arredondamento, histórico e filtros, resumo do mês no fuso de São Paulo, registro e login, senha fraca, e-mail inválido, token expirado e revogado, erros em JSON, limites de requisição, IP falso, CORS e seeder |
| Vitest (frontend) | 136 | Cliente HTTP e sessão expirada, chave de idempotência, stores, guard de rotas, menu do celular, máscara e leitura de valores, formulários, painel, extrato, datas do filtro, toasts e medidor de senha |

A maior parte dos testes de backend é de cenários de falha. O CI também cobra o padrão de código: Pint (PSR-12) e Larastan (nível 6) no backend; Prettier (100 colunas), oxlint, ESLint e checagem de tipos com `vue-tsc` no frontend.

## Pré-requisitos

Com Docker (recomendado), basta o **Docker** com **Docker Compose**. As versões ficam nas imagens:

| Ferramenta | Versão |
|---|---|
| PHP | 8.4 (extensões `pdo_pgsql`, `bcmath`, `zip`) |
| Composer | 2 |
| Node.js | 24 (npm 11) |
| PostgreSQL | 16 |

No Linux e no WSL, os containers rodam com o usuário `1000:1000`. Se o seu usuário tiver outro UID (veja com `id -u`), ajuste o campo `user` dos serviços `backend` e `frontend` no `compose.yaml`.

## Como rodar localmente

**1. Clonar e configurar o `.env`**

```bash
git clone https://github.com/flemuh/fintech-wallet-pleno.git
cd fintech-wallet-pleno
cp backend/.env.example backend/.env
```

O `.env.example` já vem com os valores do banco do Docker (`DB_HOST=db`, banco `wallet`, usuário `wallet`, senha `secret`), a origem do frontend em `CORS_ALLOWED_ORIGINS` e a expiração do token. Não é preciso editar nada para rodar.

**2. Instalar as dependências do backend**

```bash
docker compose build backend
docker compose run --rm --no-deps backend composer install
```

As dependências do frontend são instaladas sozinhas quando o container sobe.

**3. Subir os containers**

```bash
docker compose up -d
```

**4. Gerar a chave e rodar migrations e seeders**

```bash
docker compose exec backend php artisan key:generate
docker compose exec backend php artisan migrate --seed
```

O seeder cria o usuário de teste `demo@wallet.speedlora.app` / `Demo@12345` e pode rodar de novo sem duplicar nada.

**5. Abrir**

- Frontend: http://localhost:5173
- API: http://localhost:8000/api/health

A primeira subida do frontend demora alguns segundos por causa do `npm install`. Acompanhe com `docker compose logs -f frontend`.

**Rodar os testes**

```bash
docker compose exec backend composer check     # Pint, Larastan e PHPUnit
docker compose exec frontend npm run check     # formatação, lint, tipos, Vitest e build
```

Os testes do backend usam o banco `wallet_test`, criado automaticamente pelo container do PostgreSQL, e não tocam nos dados de desenvolvimento.

**Comandos úteis**

| Para | Comando |
|---|---|
| Parar tudo | `docker compose down` |
| Ver logs | `docker compose logs -f backend` |
| Zerar o banco | `docker compose down -v` e repetir os passos 3 e 4 |
| Reinstalar dependências do frontend | depois, `docker compose restart frontend` |

**Sem Docker**

Com PHP 8.4, Composer 2, Node 24 e PostgreSQL 16 instalados: crie os bancos `wallet` e `wallet_test`, copie o `.env.example` trocando `DB_HOST=db` por `DB_HOST=127.0.0.1`, e rode `composer install`, `php artisan key:generate`, `php artisan migrate --seed` e `php artisan serve` em `backend/`. Em `frontend/`, rode `npm install` e `npm run dev`; o proxy do Vite aponta para `http://localhost:8000` por padrão.

## Deploy e CI/CD

A aplicação roda em uma VPS própria com Docker Compose: um container Nginx com o Vue já compilado (que repassa `/api`), um com PHP-FPM e Laravel e um com PostgreSQL, atrás do Nginx do host, que cuida do HTTPS.

Todo push passa pelo GitHub Actions: auditoria de dependências, Pint, Larastan e PHPUnit em PostgreSQL no backend; lint, tipos, Vitest e build no frontend. Na `main`, o deploy só começa com os dois jobs verdes **e** aprovação manual no ambiente `production`. O script de deploy reconstrói as imagens, sobe os containers, roda as migrations e só termina com sucesso quando `/api/health` responde.

O trabalho é feito na branch `dev`, com commits no padrão Conventional Commits em português, e chega à `main` por Pull Request com merge commit, preservando o histórico.

## Limitações conhecidas e próximos passos

- **Alguns segundos fora do ar a cada deploy.** O script reconstrói o container do backend, que roda as migrations antes de voltar a responder. Hoje o risco é baixo: só o dono do projeto faz deploy, e a aprovação manual escolhe o momento. Para eliminar a parada, o caminho comum é o deploy blue-green ou rolling: o container novo sobe ao lado do antigo, passa no health check (`/api/health`) e só então o Nginx troca o tráfego. As migrations precisam funcionar com as duas versões ao mesmo tempo (primeiro adicionar, depois remover). Ferramentas como o Kamal e o docker-rollout fazem essa troca com Docker.
- **As chaves de idempotência não expiram.** A tabela cresce uma linha por operação feita com chave. O próximo passo seria apagar as de mais de 24 horas, prazo que o Stripe também usa, com o `Prunable` do Eloquent e o `model:prune` agendado.
- **Senhas conhecidas de vazamentos não são recusadas.** O cadastro exige senha forte, mas aceita uma senha que cumpre as regras e já apareceu em vazamentos. O risco é o ataque com listas de senhas vazadas, que o limite de 5 tentativas de login por minuto já reduz. A correção é uma regra no `RegisterRequest`, `Password::uncompromised()`, que consulta o Have I Been Pwned por k-anonymity: só os 5 primeiros caracteres do hash SHA-1 da senha saem do servidor. Ficou de fora do MVP porque faz uma chamada externa a cada cadastro. Com ela, os testes usariam `Http::fake()`, e o cadastro continua liberado se o serviço cair.
- **Alertas moderados nas ferramentas de desenvolvimento** (`minimatch`, dentro do ESLint e do `@vue/test-utils`). Não entram na imagem de produção, e a correção exige trocar versões major; ficam para depois da entrega.
- **Um fuso para todos.** Quem usa o app fora do Brasil vê as datas e os filtros no horário de Brasília. O próximo passo seria guardar o fuso de cada usuário, preenchido no cadastro a partir do navegador (`Intl.DateTimeFormat().resolvedOptions().timeZone`) e validado com `timezone:all`, e usá-lo nos cálculos e na exibição.
