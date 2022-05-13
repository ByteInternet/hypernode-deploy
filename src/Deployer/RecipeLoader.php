<?php

namespace Hypernode\Deploy\Deployer;

use RuntimeException;

class RecipeLoader
{
    /**
     * @param string $recipe
     */
    public function load(string $recipe): void
    {
        $recipe = ltrim($recipe, DIRECTORY_SEPARATOR);

        foreach ($this->getRecipePaths() as $path) {
            $path = rtrim($path, DIRECTORY_SEPARATOR);
            $file = $path . DIRECTORY_SEPARATOR . $recipe;
            if (!is_readable($file)) {
                continue;
            }

            /** @noinspection PhpIncludeInspection */
            require $file;
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Recipe %s not found. Used include paths %s',
                $recipe,
                implode(', ', $this->getRecipePaths())
            )
        );
    }

    /**
     * @return string[]
     */
    private function getRecipePaths(): array
    {
        /** @psalm-suppress UndefinedConstant */
        return [
            APPLICATION_ROOT . '/vendor/deployer/deployer/recipe',
            APPLICATION_ROOT . '/vendor/deployer/recipes/recipe',
        ];
    }
}
