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

/* themes/contrib/gin/templates/form/input--checkbox.html.twig */
class __TwigTemplate_a9b25c3631f2f64eaee947b96efc20b5 extends Template
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
        // line 14
        if ((($tmp = ($context["autocomplete_message"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 15
            yield "  <div class=\"claro-autocomplete\">
    <input";
            // line 16
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
            yield "/>
    <div hidden class=\"claro-autocomplete__message\">";
            // line 17
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("autocomplete_message", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "autocomplete_message");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "autocomplete_message");
                }
            }
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["autocomplete_message"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("autocomplete_message", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "autocomplete_message");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "autocomplete_message");
                }
            }
            yield "</div>
  </div>
  ";
            // line 19
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("children", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "children");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "children");
                }
            }
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["children"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("children", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "children");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "children");
                }
            }
            yield "
";
        } else {
            // line 21
            yield "  <input";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
            yield "/>";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("children", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "children");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "children");
                }
            }
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["children"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("children", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "children");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "children");
                }
            }
            yield "
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["autocomplete_message", "attributes", "children"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/gin/templates/form/input--checkbox.html.twig";
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
        return array (  91 => 21,  72 => 19,  53 => 17,  49 => 16,  46 => 15,  44 => 14,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/gin/templates/form/input--checkbox.html.twig", "/var/www/html/web/themes/contrib/gin/templates/form/input--checkbox.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 14];
        static $filters = ["escape" => 16];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape'],
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
