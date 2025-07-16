<?php

namespace Mishal\DocumentEditor\Tests\Feature;

use Mishal\DocumentEditor\Tests\TestCase;

class DocumentEditorTest extends TestCase
{
    public function test_blade_directive_exists(): void
    {
        $this->assertEquals(
            '<?php echo \Mishal\DocumentEditor\render_editor(); ?>',
            $this->blade('@documentEditor')
        );
    }

    public function test_can_render_editor_with_content(): void
    {
        $content = '# Markdown Heading';
        $html = render_editor($content);

        $this->assertStringContainsString('document-editor-root', $html);
        $this->assertStringContainsString(htmlspecialchars(json_encode($content)), $html);
    }

    public function test_can_render_editor_with_custom_config(): void
    {
        $options = [
            'theme' => 'dark',
            'height' => 800,
        ];

        $html = render_editor('', $options);

        $this->assertStringContainsString('theme":"dark"', $html);
        $this->assertStringContainsString('height":800', $html);
    }
}
