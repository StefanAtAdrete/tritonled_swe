# Beslutslogg

Bestående beslut som alla människor och LLM-agenter ska följa.

| Datum | Beslut | Motivering | Status |
|---|---|---|---|
| 2026-10-18 | `AGENTS.md` är gemensam kanonisk agentinstruktion. | Samma arbetsregler ska gälla oavsett LLM. | Gäller |
| 2026-10-18 | GitHub `main` är kodens sanningskälla; VPS är körmiljö. | GitHub ska vara mer än backup och möjliggöra spårbar deploy. | Gäller |
| 2026-10-18 | Produktionsdeploy startas manuellt via GitHub Actions Environment `production`. | Mänskligt beslut behålls tills flödet är beprövat. | Gäller |
| 2026-10-18 | Deploynyckeln begränsas till `deploy <commit-SHA>` från GitHub `main`. | En komprometterad Actions-secret ska inte ge generell shellåtkomst. | Gäller |
| 2026-10-18 | Tredjeparts-actions låses till verifierade commit-SHA:n. | Minskar risken att en flyttad tagg ändrar CI- eller deploykod. | Gäller |
| 2026-10-18 | Deploy stoppas om intervallet innehåller `config/sync`-ändringar. | Produktion har oförklarad config-drift; automatisk import kan skriva över korrekt driftkonfiguration. | Tillfälligt skydd |
| 2026-10-18 | Varje handoff dokumenterar utfört, verifierat, återstående och ej verifierat. | Förhindrar att olika modeller presenterar antaganden som genomfört arbete. | Gäller |
