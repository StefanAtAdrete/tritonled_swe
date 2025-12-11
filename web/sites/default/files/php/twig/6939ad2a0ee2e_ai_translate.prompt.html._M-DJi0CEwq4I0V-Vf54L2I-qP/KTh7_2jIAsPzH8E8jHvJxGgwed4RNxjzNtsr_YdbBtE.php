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

/* @help_topics/ai_translate.prompt.html.twig */
class __TwigTemplate_a6c33ea30c841a50280b5fa0f7e531b1 extends Template
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
        yield t("What is a prompt?", []);
        yield "</h2>
<p>";
        // line 6
        yield t("A <em>prompt</em> is a text sent to an AI provider in order to achieve a certain task.", []);
        yield "</p>
<h2>";
        // line 7
        yield t("What is Twig?", []);
        yield "</h2>
<p>";
        // line 8
        yield t("Twig is a modern template engine for PHP, used as default rendering engine by Drupal.", []);
        yield "</p>
<h2>";
        // line 9
        yield t("Example of using conditions in the prompt", []);
        yield "</h2>
<p>In the following example, AI translate module will use more specific
prompt for translating from Swedish to German (having additional \"Make sure...\" sentence),
and default prompt for all other cases:</p>
<pre>";
        // line 21
        yield "
{% if source_lang == 'sv' and dest_lang == 'de' %}
  Take the following text and translate it from Swedish to German word by word. Make sure that you try to understand from context if the person being spoken to is in a position where they are allowed to say 'du', otherwise change this to 'sie':
  {{ text }}
{% else %}
  Take the following text and translate it from {{ source_lang_name }} to {{ dest_lang_name }} word by word.
  {{ text }}
{% endif %}
";
        yield "</pre>
<h2>";
        // line 22
        yield t("Additional resources", []);
        yield "</h2>
<ul>
  <li><a href=\"https://twig.symfony.com/\">";
        // line 24
        yield t("Twig documentation", []);
        yield "</a></li>
  <li><a href=\"https://www.drupal.org/docs/develop/theming-drupal/twig-in-drupal\">";
        // line 25
        yield t("Twig in Drupal", []);
        yield "</a></li>
</ul>";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@help_topics/ai_translate.prompt.html.twig";
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
        return array (  89 => 25,  85 => 24,  80 => 22,  68 => 21,  61 => 9,  57 => 8,  53 => 7,  49 => 6,  44 => 5,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@help_topics/ai_translate.prompt.html.twig", "/var/www/html/web/modules/contrib/ai/modules/ai_translate/help_topics/ai_translate.prompt.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["trans" => 5];
        static $filters = [];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['trans'],
                [],
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
