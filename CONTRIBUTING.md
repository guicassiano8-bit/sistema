# Fluxo de trabalho

Este é um projeto pessoal, mas seguimos um fluxo profissional para manter o histórico limpo e o código sempre em estado publicável.

## Branches

- `main` — sempre estável e implantável. **Protegida**: só recebe merge via Pull Request.
- `feature/<descrição-curta>` — nova funcionalidade (ex: `feature/missoes-recorrentes`)
- `fix/<descrição-curta>` — correção de bug (ex: `fix/estorno-xp-duplicado`)
- `chore/<descrição-curta>` — infraestrutura, dependências, configuração
- `docs/<descrição-curta>` — documentação

Sem branch `develop`: projeto pequeno, um único desenvolvedor, `main` sempre íntegra é suficiente. Se o projeto crescer, reavaliar.

## Commits — Conventional Commits

Formato: `tipo(escopo): descrição no imperativo`

| Tipo | Uso |
|---|---|
| `feat` | nova funcionalidade |
| `fix` | correção de bug |
| `refactor` | mudança interna sem alterar comportamento |
| `docs` | documentação |
| `test` | testes |
| `chore` | build, dependências, configuração |
| `style` | formatação, sem mudança de lógica |

Exemplos:

```
feat(missoes): adiciona transferência de tarefa entre dias
fix(financeiro): corrige arredondamento no relatório de patrimônio
chore(deps): atualiza laravel/boost para 1.2.0
```

Escopo = módulo (`missoes`, `recompensas`, `inventario`, `financeiro`, `design-system`, `auth`, `infra`).

## Pull Requests

1. Toda mudança de código passa por PR para `main`, mesmo sendo o único colaborador — mantém o histórico revisável e o CI roda antes do merge.
2. Preencha o template do PR (checklist de testes, migrations, diretrizes).
3. CI (`.github/workflows/ci.yml`) precisa passar: testes, migrations e lint (Pint).
4. Squash merge, com a mensagem final seguindo Conventional Commits.
5. Apague a branch após o merge.

## Issues

Cada card do quadro de tarefas vira uma Issue, usando os templates de `feature` ou `bug`. A Issue é referenciada no PR com `Closes #<número>` para fechar automaticamente ao mergear.

## Versionamento

Tags seguindo [SemVer](https://semver.org/lang/pt-BR/): `v0.1.0`, `v0.2.0`... Major (`v1.0.0`) só quando o sistema estiver em uso diário e estável.

## Boost

Após alterar `.ai/guidelines/sistema-pessoal.md`, rode `php artisan boost:update` e commit o resultado junto (`chore: atualiza guidelines do Boost`).
