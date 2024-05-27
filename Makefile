.PHONY: help
help: ## Displays this list of targets with descriptions
	@echo "The following commands are available:\n"
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: test-lint
test-lint: ## Regenerate code snippets
	Build/Scripts/runTests.sh -s lint -p 8.2

.PHONY: test-cgl
test-cgl: ## Regenerate code snippets
	Build/Scripts/runTests.sh -s cgl -p 8.2

.PHONY: test-unit
test-unit: ## Regenerate code snippets
	Build/Scripts/runTests.sh -s unit -p 8.2

.PHONY: test
test: test-lint test-cgl phpstan test-unit## Test the documentation rendering

.PHONY: rector
rector: ## Run rector
	Build/Scripts/runTests.sh -s rector -p 8.2

.PHONY: phpstan
phpstan: ## Run rector
	Build/Scripts/runTests.sh -s phpstan -p 8.2

.PHONY: phpstan-baseline
phpstan-baseline: ## Run rector
	Build/Scripts/runTests.sh -s phpstanBaseline -p 8.2

.PHONY: fix
fix: rector test-cgl## Fix the code
