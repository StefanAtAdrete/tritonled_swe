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

/* core/themes/claro/templates/status-report-counter.html.twig */
class __TwigTemplate_1acf2720c1c49e112dd23ea966117470 extends Template
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
        // line 15
        $context["classes"] = ["system-status-counter", ("system-status-counter--" .         // line 17
($context["severity"] ?? null))];
        // line 20
        yield "<span";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 20), "html", null, true);
        yield ">
  <span class=\"system-status-counter__status-icon system-status-counter__status-icon--";
        // line 21
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["severity"] ?? null), "html", null, true);
        yield "\"></span>
  <span class=\"system-status-counter__status-title\">
    <span class=\"system-status-counter__title-count\">";
        // line 23
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
            if (array_key_exists("canvas_slot_ids", $context) && in_array("amount", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "amount");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "amount");
            }
        }
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["amount"] ?? null), "html", null, true);
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
            if (array_key_exists("canvas_slot_ids", $context) && in_array("amount", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "amount");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "amount");
            }
        }
        yield " ";
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
            if (array_key_exists("canvas_slot_ids", $context) && in_array("text", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "text");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "text");
            }
        }
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["text"] ?? null), "html", null, true);
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
            if (array_key_exists("canvas_slot_ids", $context) && in_array("text", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "text");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "text");
            }
        }
        yield "</span>
    <span class=\"system-status-counter__details\"><a href=\"#";
        // line 24
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["severity"] ?? null), "html", null, true);
        yield "\" ><span class=\"visually-hidden\">";
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
            if (array_key_exists("canvas_slot_ids", $context) && in_array("text", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "text");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "text");
            }
        }
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["text"] ?? null), "html", null, true);
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
            if (array_key_exists("canvas_slot_ids", $context) && in_array("text", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "text");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "text");
            }
        }
        yield " </span>";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Details"));
        yield "</a></span>
  </span>
</span>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["severity", "attributes", "amount", "text"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "core/themes/claro/templates/status-report-counter.html.twig";
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
        return array (  91 => 24,  57 => 23,  52 => 21,  47 => 20,  45 => 17,  44 => 15,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "core/themes/claro/templates/status-report-counter.html.twig", "/var/www/html/web/core/themes/claro/templates/status-report-counter.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 15];
        static $filters = ["escape" => 20, "t" => 24];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['set'],
                ['escape', 't'],
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
