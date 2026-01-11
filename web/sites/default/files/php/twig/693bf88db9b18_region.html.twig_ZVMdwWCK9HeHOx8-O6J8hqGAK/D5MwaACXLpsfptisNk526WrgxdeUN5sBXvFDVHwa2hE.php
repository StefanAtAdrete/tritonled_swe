<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* themes/contrib/gin/templates/regions/region.html.twig */
class __TwigTemplate_fdfaf2c0e4ee73c6b1e3352e2b762240 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 16
        $context["classes"] = ["region", ("region-" . \Drupal\Component\Utility\Html::getClass(        // line 18
($context["region"] ?? null)))];
        // line 21
        yield "
";
        // line 22
        if ((($tmp = ($context["content"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 23
            yield "  <div";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 23), "html", null, true);
            yield ">
    ";
            // line 24
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("content", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "content");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "content");
                }
            }
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["content"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("content", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "content");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "content");
                }
            }
            yield "
  </div>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["region", "content", "attributes"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/gin/templates/regions/region.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  57 => 24,  52 => 23,  50 => 22,  47 => 21,  45 => 18,  44 => 16,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/gin/templates/regions/region.html.twig", "/var/www/html/web/themes/contrib/gin/templates/regions/region.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 16, "if" => 22];
        static $filters = ["clean_class" => 18, "escape" => 23];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set', 'if'],
                ['clean_class', 'escape'],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
