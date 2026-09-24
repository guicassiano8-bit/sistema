#!/usr/bin/env bash
# Configura o repositório no GitHub de forma profissional: labels, milestones,
# proteção de branch e project board. Requer `gh` autenticado (gh auth login).
#
# Uso: ./setup-github.sh <owner>/<repo>
set -euo pipefail

REPO="${1:?Uso: ./setup-github.sh <owner>/<repo>}"

echo "==> Configurando labels em $REPO"

# Remove labels padrão que não usaremos e cria o conjunto do projeto
gh label create "feature"      --repo "$REPO" --color "1D76DB" --description "Nova funcionalidade" --force
gh label create "bug"          --repo "$REPO" --color "D73A4A" --description "Algo quebrado"        --force
gh label create "refactor"     --repo "$REPO" --color "5319E7" --description "Melhoria interna sem mudar comportamento" --force
gh label create "docs"         --repo "$REPO" --color "0075CA" --description "Documentação"          --force
gh label create "chore"        --repo "$REPO" --color "BFD4F2" --description "Build, deps, config"   --force
gh label create "design"       --repo "$REPO" --color "F5A623" --description "Design system / UI"    --force
gh label create "modulo:missoes"     --repo "$REPO" --color "0E8A16" --force
gh label create "modulo:recompensas" --repo "$REPO" --color "0E8A16" --force
gh label create "modulo:inventario"  --repo "$REPO" --color "0E8A16" --force
gh label create "modulo:financeiro"  --repo "$REPO" --color "0E8A16" --force
gh label create "prioridade:alta"    --repo "$REPO" --color "B60205" --force
gh label create "prioridade:media"   --repo "$REPO" --color "FBCA04" --force
gh label create "prioridade:baixa"   --repo "$REPO" --color "C2E0C6" --force

echo "==> Criando milestones (fases do projeto)"

for m in \
  "Fase 0 - Preparação" \
  "Fase 1 - Design System" \
  "Fase 2 - Autenticação" \
  "Fase 3 - Núcleo de gamificação" \
  "Fase 4 - Missões" \
  "Fase 5 - Loja do Sistema" \
  "Fase 6 - Inventário" \
  "Fase 7 - Tesouro" \
  "Fase 8 - Dashboard" \
  "Fase 9 - Polimento" \
  "Fase 10 - Deploy"
do
  gh api "repos/$REPO/milestones" -f title="$m" >/dev/null 2>&1 || echo "   (milestone '$m' já existe)"
done

echo "==> Protegendo a branch main"

gh api \
  --method PUT \
  -H "Accept: application/vnd.github+json" \
  "repos/$REPO/branches/main/protection" \
  -f "required_status_checks[strict]=true" \
  -f "required_status_checks[contexts][]=test" \
  -f "enforce_admins=true" \
  -f "required_pull_request_reviews[required_approving_review_count]=0" \
  -f "restrictions=null" \
  -f "allow_force_pushes=false" \
  -f "allow_deletions=false" \
  || echo "   (ajuste manual pode ser necessário se o repo for privado no plano free — veja o README)"

echo "==> Criando o Project (board)"

gh project create --owner "@me" --title "Sistema Pessoal"

echo "Pronto. Vincule o project criado ao repositório em:"
echo "https://github.com/$REPO/settings"
