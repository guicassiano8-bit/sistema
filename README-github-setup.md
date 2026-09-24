# Configuração profissional do repositório — passo a passo

## 1. Estrutura de arquivos

Copie estes arquivos para a raiz do projeto Laravel:

```
.github/
  CODEOWNERS
  PULL_REQUEST_TEMPLATE.md
  ISSUE_TEMPLATE/
    feature.md
    bug.md
  workflows/
    ci.yml
CONTRIBUTING.md
```

No `CODEOWNERS`, troque `@guilherme` pelo seu usuário do GitHub.

## 2. Criar o repositório

```bash
gh repo create sistema-pessoal --private --source=. --remote=origin
git add .
git commit -m "chore: configura repositório (templates, CI, contributing)"
git push -u origin main
```

Repositório **privado**: é um sistema pessoal com dados financeiros seus, não deve ser público.

## 3. Rodar o script de setup

Requer o [GitHub CLI](https://cli.github.com/) instalado e autenticado (`gh auth login`).

```bash
chmod +x setup-github.sh
./setup-github.sh SEU_USUARIO/sistema-pessoal
```

O script cria:
- **Labels** por tipo (`feature`, `bug`, `refactor`, `docs`, `chore`, `design`) e por módulo (`modulo:missoes`, `modulo:financeiro` etc.), além de prioridade.
- **Milestones** — uma por fase, espelhando as fases do quadro de tarefas (Fase 0 a Fase 10).
- **Proteção da branch `main`** — exige que o CI passe antes do merge, bloqueia push direto e exclusão da branch.
- **Um GitHub Project (board)** — visão Kanban equivalente ao Trello, dentro do próprio GitHub, ligando Issues e PRs automaticamente.

> Nota: em repositório privado no plano gratuito do GitHub, algumas regras de proteção de branch (como exigir revisão de PR) podem ter suporte limitado. O script já usa `required_approving_review_count=0` por você trabalhar sozinho — a proteção real vem do CI obrigatório.

## 4. Migrar os cards do Trello

Cada card do quadro (o passo a passo que já montamos) vira uma Issue:

```bash
gh issue create \
  --repo SEU_USUARIO/sistema-pessoal \
  --title "[feat] Estrutura de XP e ouro" \
  --body "Migration xp_total/gold, tabela point_transactions, LevelService." \
  --label "feature,modulo:missoes,prioridade:alta" \
  --milestone "Fase 3 - Núcleo de gamificação"
```

Repita para os demais cards, ou use o board do GitHub Project diretamente e crie os itens por lá (mais rápido para quem prefere interface visual).

## 5. Fluxo do dia a dia

```bash
git checkout main && git pull
git checkout -b feature/xp-e-ouro

# ...trabalha, com o Claude Code seguindo .ai/guidelines/sistema-pessoal.md...

git add .
git commit -m "feat(missoes): adiciona LevelService e tabela point_transactions"
git push -u origin feature/xp-e-ouro

gh pr create --fill --base main
```

O PR abre com o template preenchido, o CI roda automaticamente, e ao mergear (squash) a Issue vinculada com `Closes #N` fecha sozinha.

## 6. Versionamento

Ao fechar uma fase importante (ex: fim da Fase 4 — Missões funcionando ponta a ponta):

```bash
git tag -a v0.4.0 -m "Missões: CRUD, recorrência, transferência entre dias"
git push origin v0.4.0
gh release create v0.4.0 --generate-notes
```

O `--generate-notes` monta o changelog automaticamente a partir dos PRs mergeados, no estilo de release notes de empresa.
