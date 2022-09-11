<?php

declare(strict_types=1);

namespace Symbiotic\View\Blade;

use Symbiotic\Packages\TemplateCompilerInterface;

/**
 * @use Blade::compile($string);
 **/
class Blade implements TemplateCompilerInterface
{

    /**
     * All of the compiler functions used by Blade.
     *
     * @var array
     */
    protected static array $compilers = array(
        'extensions',
        'comments',
        'php',
        'define',
        'echos',
        'forelse',
        'empty',
        'endforelse',
        'structure_openings',
        'structure_closings',
        'else',
        'unless',
        'endunless',
        'includes',
        'render',
        'lang',
        'action',
        'yields',
        'yield_sections',
        'section_start',
        'section_end',
        'url',
        'asset',
        'hasSection',
        'route',
        'show',
    );

    /**
     * An array of user defined compilers.
     *
     * @var array
     */
    protected static array $extensions = array();

    /**
     * Register a custom Blade compiler.
     *
     * <code>
     *        Blade::extend(function($view)
     *        {
     *            return str_replace('foo', 'bar', $view);
     *        });
     * </code>
     *
     * @param \Closure $compiler
     *
     * @return void
     */
    public static function extend(\Closure $compiler): void
    {
        static::$extensions[] = $compiler;
    }

    /**
     * @return string[]
     */
    public function getExtensions(): array
    {
        return ['blade', 'blade.php'];
    }

    /**
     * Compiles the given string containing Blade pseudo-code into valid PHP.
     *
     * @param string $template
     *
     * @return string
     */
    public function compile(string $template): string
    {
        foreach (static::$compilers as $compiler) {
            $method = "compile_{$compiler}";

            $template = call_user_func([$this, $method], $template);
        }
        return $this->compile_layouts($template);
    }


    /**
     * Rewrites Blade "@layout" expressions into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_layouts(string $value): string
    {
        // If the Blade template is not using "layouts", we'll just return it
        // unchanged since there is nothing to do with layouts and we will
        // just let the other Blade compilers handle the rest.
        if ((str_contains($value, '@layout'))) {
            $key = 'layout';
        } elseif ((str_contains($value, '@extends'))) {
            $key = 'extends';
        } else {
            return $value;
        }

        //Strip end of file
        $value = rtrim($value);


        preg_match('/@' . $key . '(\s*\(.*\))(\s*)/', $value, $matches);
        $layout = str_replace(array("('", "')", '("', ')"'), '', $matches[1]);
        // First we'll split out the lines of the template so we can get the
        // layout from the top of the template. By convention it must be
        // located on the first line of the template contents.
        $lines = preg_split("/(\r?\n)/", $value);

        $code = implode(PHP_EOL, array_slice($lines, 1));
        // We will add a "render" statement to the end of the templates and
        // then slice off the "@layout" shortcut from the start so the
        // sections register before the parent template renders.
        return '<?php echo $this->layout("' . $layout . '", \'' . str_replace(
                "'",
                "\'",
                $code
            ) . '\', get_defined_vars(), ' . ($key == 'extends' ? 'true' : '') . ')->render(); ?>';
    }

    protected function compile_php(string $value): string
    {
        return preg_replace('/\@php(.+?)@endphp/is', '<?php ${1}; ?>', $value);
    }

    /**
     * Rewrites Blade comments into PHP comments.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_comments(string $value): string
    {
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/', "<?php /** $1 **/ ?>\n", $value);
    }

    /**
     * Rewrites Blade echo statements into PHP echo statements.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_echos(string $value): string
    {
        $value = preg_replace('/\{!!(.+?)!!\}/', '<?php echo $1; ?>', $value);

        return preg_replace('/\{\{(.+?)\}\}/', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\', false); ?>', $value);
    }

    /**
     * Rewrites Blade echo statements into PHP echo statements.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_define(string $value): string
    {
        return preg_replace('/\{\{\{(.+?)\}\}\}/', '<?php  $1;  ?>', $value);
    }

    /**
     * Rewrites Blade "for else" statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_forelse(string $value): string
    {
        preg_match_all('/(\s*)@forelse(\s*\(.*\))(\s*)/', $value, $matches);

        foreach ($matches[0] as $forelse) {
            preg_match('/\s*\(\s*(\S*)\s/', $forelse, $variable);

            // Once we have extracted the variable being looped against, we can add
            // an if statement to the start of the loop that checks if the count
            // of the variable being looped against is greater than zero.
            $if = "<?php if (count({$variable[1]}) > 0): ?>";

            $search = '/(\s*)@forelse(\s*\(.*\))/';

            $replace = '$1' . $if . '<?php foreach$2: ?>';

            $blade = preg_replace($search, $replace, $forelse);

            // Finally, once we have the check prepended to the loop we'll replace
            // all instances of this forelse syntax in the view content of the
            // view being compiled to Blade syntax with real PHP syntax.
            $value = str_replace($forelse, $blade, $value);
        }

        return $value;
    }

    /**
     * Rewrites Blade "empty" statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_empty(string $value): string
    {
        $value = str_replace('@empty', '<?php endforeach; ?><?php else: ?>', $value);
        return str_replace('@continue', '<?php continue; ?>', $value);
    }

    /**
     * Rewrites Blade "forelse" endings into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_endforelse(string $value): string
    {
        return str_replace('@endforelse', '<?php endif; ?>', $value);
    }

    /**
     * Rewrites Blade structure openings into PHP structure openings.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_structure_openings(string $value): string
    {
        $pattern = '/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/';

        return preg_replace($pattern, '$1<?php $2$3: ?>', $value);
    }

    /**
     * Rewrites Blade structure closings into PHP structure closings.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_structure_closings(string $value): string
    {
        $pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

        return preg_replace($pattern, '$1<?php $2; ?>$3', $value);
    }

    /**
     * Rewrites Blade else statements into PHP else statements.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_else(string $value): string
    {
        return preg_replace('/(\s*)@(else)(\s*)/', '$1<?php $2: ?>$3', $value);
    }

    /**
     * Rewrites Blade "unless" statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_unless(string $value): string
    {
        $pattern = '/(\s*)@unless(\s*\(.*\))/';

        return preg_replace($pattern, '$1<?php if ( ! ($2)): ?>', $value);
    }

    /**
     * Rewrites Blade "unless" endings into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_endunless(string $value): string
    {
        return str_replace('@endunless', '<?php endif; ?>', $value);
    }

    /**
     * Rewrites Blade @include statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_includes(string $value): string
    {
        $pattern = $this->matcher('include');
        //todo: test new realisation
        return preg_replace($pattern, '$1<?php echo $this->include$2->render(); ?>', $value);
    }

    /**
     * Get the regular expression for a generic Blade function.
     *
     * @param string $function
     *
     * @return string
     */
    public function matcher(string $function): string
    {
        return '/(\s*)@' . $function . '(\s*\(.*\))/';
    }

    /**
     * Rewrites Blade @include statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_action(string $value): string
    {
        $pattern = $this->matcher('action');

        return preg_replace($pattern, '$1<?php echo $this->action$2->render(); ?>', $value);
    }

    /**
     * Rewrites Blade @lang statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_lang(string $value): string
    {
        $pattern = $this->matcher('lang');

        return preg_replace($pattern, '$1<?php echo $this->lang$2; ?>', $value);
    }

    /**
     * Rewrites Blade @include statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_hasSection(string $value): string
    {
        $pattern = $this->matcher('hasSection');

        return preg_replace($pattern, '$1<?php if($this->hasSection$2): ?>', $value);
    }

    /**
     * Rewrites Blade @render statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_render(string $value): string
    {
        $pattern = $this->matcher('render');

        return preg_replace($pattern, '$1<?php echo $this->render$2->render(); ?>', $value);
    }

    /**
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_url(string $value): string
    {
        $pattern = $this->matcher('url');
        return preg_replace($pattern, '$1<?php echo  $this->url$2; ?>', $value);
    }

    /**
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_asset(string $value): string
    {
        $pattern = $this->matcher('asset');
        return preg_replace($pattern, '$1<?php echo  $this->asset$2; ?>', $value);
    }

    /**
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_route(string $value): string
    {
        $pattern = $this->matcher('route');
        return preg_replace($pattern, '$1<?php echo  $this->route$2; ?>', $value);
    }

    /**
     * Rewrites Blade @render_each statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    /*  protected function compile_render_each(string $value):string
      {
          $pattern = $this->matcher('render_each');

          return preg_replace($pattern, '$1<?php echo $this->render_each$2; ?>', $value);
      }*/

    /**
     * Rewrites Blade @yield statements into Section statements.
     *
     * The Blade @yield statement is a shortcut to the Section::yield method.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_yields(string $value): string
    {
        $pattern = $this->matcher('yield');
        return preg_replace($pattern, '$1<?php echo  $this->yield$2; ?>', $value);
    }

    /**
     * Rewrites Blade yield section statements into valid PHP.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_yield_sections(string $value): string
    {
        return str_replace('@yield_section', '<?php echo  $this->sectionYieldSection(); ?>', $value);
    }

    /**
     * Rewrites Blade @section statements into Section statements.
     *
     * The Blade @section statement is a shortcut to the Section::start method.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_section_start(string $value): string
    {
        $pattern = $this->matcher('section');

        return preg_replace($pattern, '$1<?php $this->start$2; ?>', $value);
    }

    /**
     * Rewrites Blade @endsection statements into Section statements.
     *
     * The Blade @endsection statement is a shortcut to the Section::stop method.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_section_end(string $value): string
    {
        return preg_replace('/@endsection|@stop/', '<?php $this->stop(); ?>', $value);
    }

    /**
     * Rewrites Blade @endsection statements into Section statements.
     *
     * The Blade @endsection statement is a shortcut to the Section::stop method.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_show(string $value): string
    {
        return preg_replace('/@show/', '<?php $this->yield($this->stop()); ?>', $value);
    }

    /**
     * Execute user defined compilers.
     *
     * @param string $value
     *
     * @return string
     */
    protected function compile_extensions(string $value): string
    {
        foreach (static::$extensions as $compiler) {
            $value = $compiler($value);
        }

        return $value;
    }


}