# Sistema

Sistema pessoal de produtividade e finanças, com a interface de uma janela de progressão. Um único jogador entra, cumpre missões e troca o ouro ganho por recompensas que ele mesmo cadastrou.

A interface está em português do Brasil. Datas no formato `dd/mm/aaaa`, valores em real (`R$ 1.234,56`) e fuso `America/Sao_Paulo`.

## Como funciona

Existem duas moedas, com papéis diferentes:

| | XP | Ouro |
|---|---|---|
| Serve para | Subir de nível e de rank | Comprar recompensas na loja |
| Pode diminuir? | Só no estorno de uma missão desmarcada | Sim, ao resgatar uma recompensa |

Concluir uma missão concede o XP e o ouro definidos nela. Desmarcar devolve os dois. O nível não fica gravado: é calculado a partir do XP total. Cada nível pede `100 × nível` de XP para o próximo. O rank segue a faixa de nível: E (1–9), D (10–19), C (20–29), B (30–39), A (40–49) e S (50+).

Toda movimentação de XP e ouro fica registrada, para auditoria e estorno. Resgatar uma recompensa sem ouro suficiente é recusado no servidor.

## Módulos

Os nomes temáticos existem só na interface. No código, models e tabelas usam nomes literais em inglês (`Task`, `Reward`, `ShoppingItem`, `Transaction`).

| Tela | O que é | Estado |
|---|---|---|
| **Status** | Painel do dia: missões de hoje, progresso e XP ganho | Em uso |
| **Missões** | Tarefas avulsas e recorrentes, com visões de dia, semana, mês e ano. Uma ocorrência pode ser transferida de dia sem alterar a regra | Em uso |
| **Loja** | Recompensas cadastradas pelo jogador, compradas com ouro, com histórico de resgates | Em uso |
| **Inventário** | Lista de compras. A categoria aparece como raridade: Urgente, Importante, Dia a dia, Não importante | Modelo pronto; tela ainda não ligada |
| **Tesouro** | Ganhos, gastos, orçamentos e investimentos. O financeiro não gera XP nem ouro | Modelo pronto; tela ainda não ligada |

O acesso é só por login. Não há cadastro público nem recuperação de senha: a conta é criada pelo seeder, a partir do `.env`.

## Stack

- PHP 8.2+ e [Laravel 13](https://laravel.com/docs/13.x)
- Blade, Tailwind CSS v4 e JavaScript puro, empacotados com Vite
- Banco padrão: SQLite. MySQL também serve, ajustando o `.env`
- Testes com [Pest](https://pestphp.com)

## Como rodar

Requisitos: PHP 8.2+, Composer, Node.js e npm.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

No `.env`, defina a conta do jogador. A senha precisa ter pelo menos 12 caracteres.

```dotenv
ADMIN_NAME=Jogador
ADMIN_EMAIL=jogador@example.com
ADMIN_PASSWORD=uma-senha-longa
```

Depois:

```bash
php artisan migrate
php artisan db:seed
npm install
npm run dev
```

Em outro terminal:

```bash
php artisan serve
```

O `composer run dev` sobe o servidor, a fila, os logs e o Vite juntos. O `composer run setup` instala dependências, gera a chave, migra e faz o build de produção — o seed da conta continua sendo um passo à parte.

Acesse `http://localhost:8000` e entre com o e-mail e a senha do `.env`.

## Testes

```bash
php artisan test
```
