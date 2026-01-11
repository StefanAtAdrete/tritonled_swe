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

/* themes/contrib/gin/templates/dataset/table.html.twig */
class __TwigTemplate_90508e1a2a508612f10336820f0aa4fb extends Template
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
        // line 42
        yield "
";
        // line 43
        $macros["͜macros"] = $this->macros["͜macros"] = $this;
        // line 44
        yield "
";
        // line 80
        yield "
<div class=\"layer-wrapper gin-layer-wrapper\">
  ";
        // line 82
        if ((($tmp = ($context["header"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 83
            yield "    ";
            if ((($tmp = ($context["sticky"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 84
                yield "      <table class=\"gin--sticky-table-header syncscroll\" name=\"gin-sticky-header\" hidden>
        ";
                // line 85
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["͜macros"]->getTemplateForMacro("macro_table_header", $context, 85, $this->getSourceContext())->macro_table_header(...[($context["header"] ?? null)]));
                yield "
      </table>
    ";
            }
            // line 88
            yield "  <div class=\"gin-table-scroll-wrapper gin-horizontal-scroll-shadow syncscroll\" name=\"gin-sticky-header\">
  ";
        }
        // line 90
        yield "    <table";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
        yield ">
      ";
        // line 91
        if ((($tmp = ($context["caption"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 92
            yield "        <caption>";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("caption", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "caption");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "caption");
                }
            }
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["caption"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && array_key_exists("canvas_uuid", $context)) {
                if (array_key_exists("canvas_slot_ids", $context) && in_array("caption", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "caption");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "caption");
                }
            }
            yield "</caption>
      ";
        }
        // line 94
        yield "
      ";
        // line 95
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["colgroups"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["colgroup"]) {
            // line 96
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["colgroup"], "cols", [], "any", false, false, true, 96)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 97
                yield "          <colgroup";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["colgroup"], "attributes", [], "any", false, false, true, 97), "html", null, true);
                yield ">
            ";
                // line 98
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["colgroup"], "cols", [], "any", false, false, true, 98));
                foreach ($context['_seq'] as $context["_key"] => $context["col"]) {
                    // line 99
                    yield "              <col";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["col"], "attributes", [], "any", false, false, true, 99), "html", null, true);
                    yield " />
            ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['col'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 101
                yield "          </colgroup>
        ";
            } else {
                // line 103
                yield "          <colgroup";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["colgroup"], "attributes", [], "any", false, false, true, 103), "html", null, true);
                yield " />
        ";
            }
            // line 105
            yield "      ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['colgroup'], $context['_parent']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 106
        yield "
      ";
        // line 107
        if ((($tmp = ($context["header"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 108
            yield "        ";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["͜macros"]->getTemplateForMacro("macro_table_header", $context, 108, $this->getSourceContext())->macro_table_header(...[($context["header"] ?? null)]));
            yield "
      ";
        }
        // line 110
        yield "
      ";
        // line 111
        if ((($tmp = ($context["rows"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 112
            yield "        <tbody>
          ";
            // line 113
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["rows"] ?? null));
            $context['loop'] = [
              'parent' => $context['_parent'],
              'index0' => 0,
              'index'  => 1,
              'first'  => true,
            ];
            if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
                $length = count($context['_seq']);
                $context['loop']['revindex0'] = $length - 1;
                $context['loop']['revindex'] = $length;
                $context['loop']['length'] = $length;
                $context['loop']['last'] = 1 === $length;
            }
            foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
                // line 114
                yield "            ";
                // line 115
                $context["row_classes"] = [(((($tmp =  !                // line 116
($context["no_striping"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (Twig\Extension\CoreExtension::cycle(["odd", "even"], CoreExtension::getAttribute($this->env, $this->source, $context["loop"], "index0", [], "any", false, false, true, 116))) : (""))];
                // line 119
                yield "            <tr";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["row"], "attributes", [], "any", false, false, true, 119), "addClass", [($context["row_classes"] ?? null)], "method", false, false, true, 119), "html", null, true);
                yield ">
              ";
                // line 120
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["row"], "cells", [], "any", false, false, true, 120));
                foreach ($context['_seq'] as $context["_key"] => $context["cell"]) {
                    // line 121
                    yield "                <";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "tag", [], "any", false, false, true, 121), "html", null, true);
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "attributes", [], "any", false, false, true, 121), "html", null, true);
                    yield ">";
                    // line 122
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "content", [], "any", false, false, true, 122), "html", null, true);
                    // line 123
                    yield "</";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "tag", [], "any", false, false, true, 123), "html", null, true);
                    yield ">
              ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['cell'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 125
                yield "            </tr>
          ";
                ++$context['loop']['index0'];
                ++$context['loop']['index'];
                $context['loop']['first'] = false;
                if (isset($context['loop']['revindex0'], $context['loop']['revindex'])) {
                    --$context['loop']['revindex0'];
                    --$context['loop']['revindex'];
                    $context['loop']['last'] = 0 === $context['loop']['revindex0'];
                }
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 127
            yield "        </tbody>
      ";
        } elseif ((($tmp =         // line 128
($context["empty"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 129
            yield "        <tbody>
          <tr class=\"odd\">
            <td colspan=\"";
            // line 131
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["header_columns"] ?? null), "html", null, true);
            yield "\" class=\"empty message\">";
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["empty"] ?? null), "html", null, true);
            yield "</td>
          </tr>
        </tbody>
      ";
        }
        // line 135
        yield "      ";
        if ((($tmp = ($context["footer"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 136
            yield "        <tfoot>
          ";
            // line 137
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["footer"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
                // line 138
                yield "            <tr";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["row"], "attributes", [], "any", false, false, true, 138), "html", null, true);
                yield ">
              ";
                // line 139
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["row"], "cells", [], "any", false, false, true, 139));
                foreach ($context['_seq'] as $context["_key"] => $context["cell"]) {
                    // line 140
                    yield "                <";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "tag", [], "any", false, false, true, 140), "html", null, true);
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "attributes", [], "any", false, false, true, 140), "html", null, true);
                    yield ">";
                    // line 141
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "content", [], "any", false, false, true, 141), "html", null, true);
                    // line 142
                    yield "</";
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "tag", [], "any", false, false, true, 142), "html", null, true);
                    yield ">
              ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['cell'], $context['_parent']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 144
                yield "            </tr>
          ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['row'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 146
            yield "        </tfoot>
      ";
        }
        // line 148
        yield "    </table>
  ";
        // line 149
        if ((($tmp = ($context["header"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 150
            yield "  </div>
  ";
        }
        // line 152
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["_self", "header", "sticky", "attributes", "caption", "colgroups", "rows", "no_striping", "loop", "empty", "header_columns", "footer"]);        yield from [];
    }

    // line 45
    public function macro_table_header($header = null, ...$varargs): string|Markup
    {
        $macros = $this->macros;
        $context = [
            "header" => $header,
            "varargs" => $varargs,
        ] + $this->env->getGlobals();

        $blocks = [];

        return ('' === $tmp = \Twig\Extension\CoreExtension::captureOutput((function () use (&$context, $macros, $blocks) {
            // line 46
            yield "  <thead>
    <tr>
      ";
            // line 48
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["header"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["cell"]) {
                // line 49
                yield "        ";
                if (CoreExtension::inFilter("<a", $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "content", [], "any", false, false, true, 49))))) {
                    // line 50
                    yield "          ";
                    // line 51
                    $context["cell_classes"] = ["th__item", (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 53
$context["cell"], "active_table_sort", [], "any", false, false, true, 53)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("is-active") : ("")), ((CoreExtension::inFilter("select-all", CoreExtension::getAttribute($this->env, $this->source,                     // line 54
$context["cell"], "attributes", [], "any", false, false, true, 54))) ? ("gin--sticky-bulk-select") : (""))];
                    // line 57
                    yield "        ";
                } else {
                    // line 58
                    yield "          ";
                    // line 59
                    $context["cell_classes"] = [(((($tmp = \Drupal\Component\Utility\Html::getClass($this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(CoreExtension::getAttribute($this->env, $this->source,                     // line 60
$context["cell"], "content", [], "any", false, false, true, 60)))) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? (("th__" . \Drupal\Component\Utility\Html::getClass($this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "content", [], "any", false, false, true, 60))))) : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 61
$context["cell"], "active_table_sort", [], "any", false, false, true, 61)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("is-active") : ("")), ((CoreExtension::inFilter("select-all", CoreExtension::getAttribute($this->env, $this->source,                     // line 62
$context["cell"], "attributes", [], "any", false, false, true, 62))) ? ("gin--sticky-bulk-select") : (""))];
                    // line 65
                    yield "        ";
                }
                // line 66
                yield "        <";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "tag", [], "any", false, false, true, 66), "html", null, true);
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "attributes", [], "any", false, false, true, 66), "addClass", [($context["cell_classes"] ?? null)], "method", false, false, true, 66), "html", null, true);
                yield ">";
                // line 67
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "content", [], "any", false, false, true, 67), "html", null, true);
                // line 68
                if (CoreExtension::inFilter("gin--sticky-bulk-select", ($context["cell_classes"] ?? null))) {
                    // line 69
                    yield "            <input
              type=\"checkbox\"
              class=\"form-checkbox form-boolean form-boolean--type-checkbox\"
              title=\"";
                    // line 72
                    yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Select all rows in this table"));
                    yield "\"
            />
          ";
                }
                // line 75
                yield "        </";
                yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["cell"], "tag", [], "any", false, false, true, 75), "html", null, true);
                yield ">
      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['cell'], $context['_parent']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 77
            yield "    </tr>
  </thead>
";
            yield from [];
        })())) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/gin/templates/dataset/table.html.twig";
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
        return array (  377 => 77,  368 => 75,  362 => 72,  357 => 69,  355 => 68,  353 => 67,  348 => 66,  345 => 65,  343 => 62,  342 => 61,  341 => 60,  340 => 59,  338 => 58,  335 => 57,  333 => 54,  332 => 53,  331 => 51,  329 => 50,  326 => 49,  322 => 48,  318 => 46,  306 => 45,  299 => 152,  295 => 150,  293 => 149,  290 => 148,  286 => 146,  279 => 144,  270 => 142,  268 => 141,  263 => 140,  259 => 139,  254 => 138,  250 => 137,  247 => 136,  244 => 135,  235 => 131,  231 => 129,  229 => 128,  226 => 127,  211 => 125,  202 => 123,  200 => 122,  195 => 121,  191 => 120,  186 => 119,  184 => 116,  183 => 115,  181 => 114,  164 => 113,  161 => 112,  159 => 111,  156 => 110,  150 => 108,  148 => 107,  145 => 106,  139 => 105,  133 => 103,  129 => 101,  120 => 99,  116 => 98,  111 => 97,  108 => 96,  104 => 95,  101 => 94,  81 => 92,  79 => 91,  74 => 90,  70 => 88,  64 => 85,  61 => 84,  58 => 83,  56 => 82,  52 => 80,  49 => 44,  47 => 43,  44 => 42,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/gin/templates/dataset/table.html.twig", "/var/www/html/web/themes/contrib/gin/templates/dataset/table.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["import" => 43, "macro" => 45, "if" => 82, "for" => 95, "set" => 115];
        static $filters = ["escape" => 90, "render" => 49, "clean_class" => 60, "t" => 72];
        static $functions = ["cycle" => 116];

        try {
            $this->sandbox->checkSecurity(
                ['import', 'macro', 'if', 'for', 'set'],
                ['escape', 'render', 'clean_class', 't'],
                ['cycle'],
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
