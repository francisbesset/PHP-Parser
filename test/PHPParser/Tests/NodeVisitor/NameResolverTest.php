<?php

class PHPParser_Tests_NodeVisitor_NameResolverTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers PHPParser_NodeVisitor_NameResolver
     */
    public function testResolveNames() {
        $code = <<<EOC
<?php

namespace Foo {
    use Hallo as Hi;

    new Bar();
    new Hi();
    new Hi\\Bar();
    new \\Bar();
    new namespace\\Bar();

    bar();
    hi();
    Hi\\bar();
    foo\\bar();
    \\bar();
    namespace\\bar();
}
namespace {
    use Hallo as Hi;

    new Bar();
    new Hi();
    new Hi\\Bar();
    new \\Bar();
    new namespace\\Bar();

    bar();
    hi();
    Hi\\bar();
    foo\\bar();
    \\bar();
    namespace\\bar();
}
EOC;
        $expectedCode = <<<EOC
namespace Foo {
    use Hallo as Hi;
    new \\Foo\\Bar();
    new \\Hallo();
    new \\Hallo\\Bar();
    new \\Bar();
    new \\Foo\\Bar();
    bar();
    hi();
    \\Hallo\\bar();
    \\Foo\\foo\\bar();
    \\bar();
    \\Foo\\bar();
}
namespace {
    use Hallo as Hi;
    new \\Bar();
    new \\Hallo();
    new \\Hallo\\Bar();
    new \\Bar();
    new \\Bar();
    bar();
    hi();
    \\Hallo\\bar();
    \\foo\\bar();
    \\bar();
    \\bar();
}
EOC;

        $parser        = new PHPParser_Parser;
        $prettyPrinter = new PHPParser_PrettyPrinter_Zend;
        $traverser     = new PHPParser_NodeTraverser;
        $traverser->addVisitor(new PHPParser_NodeVisitor_NameResolver);

        $stmts = $parser->parse(new PHPParser_Lexer($code));
        $stmts = $traverser->traverse($stmts);

        $this->assertEquals($expectedCode, $prettyPrinter->prettyPrint($stmts));
    }

    /**
     * @covers PHPParser_NodeVisitor_NameResolver
     */
    public function testAddNamespacedName() {
        $code = <<<EOC
<?php

namespace Foo {
    class A {}
    interface B {}
    trait C {}
    function D() {}
    const E = 'F';
}
namespace {
    class A {}
    interface B {}
    trait C {}
    function D() {}
    const E = 'F';
}
EOC;

        $parser    = new PHPParser_Parser;
        $traverser = new PHPParser_NodeTraverser;
        $traverser->addVisitor(new PHPParser_NodeVisitor_NameResolver);

        $stmts = $parser->parse(new PHPParser_Lexer($code));
        $stmts = $traverser->traverse($stmts);

        $this->assertEquals('Foo\\A', (string) $stmts[0]->stmts[0]->namespacedName);
        $this->assertEquals('Foo\\B', (string) $stmts[0]->stmts[1]->namespacedName);
        $this->assertEquals('Foo\\C', (string) $stmts[0]->stmts[2]->namespacedName);
        $this->assertEquals('Foo\\D', (string) $stmts[0]->stmts[3]->namespacedName);
        $this->assertEquals('Foo\\E', (string) $stmts[0]->stmts[4]->consts[0]->namespacedName);
        $this->assertEquals('A',      (string) $stmts[1]->stmts[0]->namespacedName);
        $this->assertEquals('B',      (string) $stmts[1]->stmts[1]->namespacedName);
        $this->assertEquals('C',      (string) $stmts[1]->stmts[2]->namespacedName);
        $this->assertEquals('D',      (string) $stmts[1]->stmts[3]->namespacedName);
        $this->assertEquals('E',      (string) $stmts[1]->stmts[4]->consts[0]->namespacedName);
    }
}