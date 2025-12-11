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

/* @help_topics/ai_translate.references.html.twig */
class __TwigTemplate_a0b39ea031ddb00143d3af1396ab99b5 extends Template
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
        // line 5
        yield "<h2>";
        yield t("AI translate and entity reference fields", []);
        yield "</h2>
<p>";
        // line 6
        yield t("Editors may want to translate all the text associated with the entity,
  i.e. not only text fields of the entity itself, but also text fields of referenced entities", []);
        // line 7
        yield "</p>
<p>";
        // line 8
        yield t("Most common examples are paragraphs and inline blocks used by layout builder", []);
        yield "</p>
<h2>";
        // line 9
        yield t("Defaults and per-field settings", []);
        yield "</h2>
";
        // line 10
        $context["module_settings_link_text"] = ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
            // line 11
            yield "  ";
            yield t("module settings", []);
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
        // line 13
        $context["help_link"] = $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\help\HelpTwigExtension']->getRouteLink(($context["module_settings_link_text"] ?? null), "ai_translate.settings_form"));
        // line 14
        yield "<p>";
        yield t("Defaults for referenced entities of each supported type
  can be set in @help_link.
  In addition, every entity reference can be set to translated to AI in field settings form", ["@help_link" => $this->env->getExtension(\Drupal\Core\Template\TwigExtension::class)->renderVar(        // line 15
($context["help_link"] ?? null)), ]);
        // line 17
        yield "</p>";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@help_topics/ai_translate.references.html.twig";
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
        return array (  78 => 17,  76 => 15,  72 => 14,  70 => 13,  65 => 11,  63 => 10,  59 => 9,  55 => 8,  52 => 7,  49 => 6,  44 => 5,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@help_topics/ai_translate.references.html.twig", "/var/www/html/web/modules/contrib/ai/modules/ai_translate/help_topics/ai_translate.references.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["trans" => 5, "set" => 10];
        static $filters = ["escape" => 15];
        static $functions = ["render_var" => 13, "help_route_link" => 13];

        try {
            $this->sandbox->checkSecurity(
                ['trans', 'set'],
                ['escape'],
                ['render_var', 'help_route_link'],
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
