<?php

declare(strict_types=1);

namespace Maarheeze\CodeGraph\Extraction;

use Maarheeze\CodeGraph\Contracts\FileVisitor;
use Maarheeze\CodeGraph\Extraction\Visitors\CallEdgeVisitor;
use Maarheeze\CodeGraph\Extraction\Visitors\ChunkExtractor;
use Maarheeze\CodeGraph\Extraction\Visitors\InheritanceEdgeVisitor;
use Maarheeze\CodeGraph\Extraction\Visitors\PseudoFileExtractor;
use Maarheeze\CodeGraph\Extraction\Visitors\SignatureBuilder;
use Maarheeze\CodeGraph\Extraction\Visitors\SymbolFactory;
use Maarheeze\CodeGraph\Extraction\Visitors\SymbolVisitor;
use Maarheeze\CodeGraph\Extraction\Visitors\TypeFormatter;
use Maarheeze\CodeGraph\Plugin\PluginRegistry;
use Maarheeze\CodeGraph\Storage\Sqlite\SqliteGraph;
use Maarheeze\CodeGraph\Values\IndexStats;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use Webmozart\Assert\Assert;

use function array_merge;
use function count;
use function sprintf;

final readonly class FileProcessor
{
    public function __construct(
        private SqliteGraph $graph,
        private Parser $parser,
        private ExtractorRegistry $registry = new ExtractorRegistry(),
        private PluginRegistry $pluginRegistry = new PluginRegistry(),
    ) {
    }

    /**
     * @param array<string, mixed> $fileData
     */
    public function process(array $fileData, IndexStats $stats): void
    {
        $relPath = $fileData['relPath'];
        $contents = $fileData['contents'];
        $sha256 = $fileData['sha256'];
        $size = $fileData['size'];
        $mtime = $fileData['mtime'];

        Assert::string($relPath);
        Assert::string($contents);
        Assert::string($sha256);
        Assert::integer($size);
        Assert::integer($mtime);

        try {
            $stmts = $this->parser->parse($contents);
        } catch (Error $e) {
            $stats->addError(sprintf('Parse error in %s: %s', $relPath, $e->getMessage()));
            $stats->incrementFilesFailed();

            return;
        }

        if ($stmts === null) {
            return;
        }

        $traverser = new NodeTraverser();

        $typeFormatter = new TypeFormatter();
        $signatureBuilder = new SignatureBuilder($typeFormatter);
        $symbolFactory = new SymbolFactory($signatureBuilder);

        $symbolVisitor = new SymbolVisitor($relPath, $contents, $symbolFactory);
        $inheritanceVisitor = new InheritanceEdgeVisitor($relPath, $contents);
        $callVisitor = new CallEdgeVisitor($relPath, $contents);

        $traverser->addVisitor($symbolVisitor);
        $traverser->addVisitor($inheritanceVisitor);
        $traverser->addVisitor($callVisitor);

        foreach ($this->registry->all() as $visitor) {
            $traverser->addVisitor($visitor);
        }

        $pluginVisitors = [];
        foreach ($this->pluginRegistry->getVisitors() as $visitorClass) {
            $visitor = new $visitorClass($relPath, $contents);
            Assert::isInstanceOf($visitor, FileVisitor::class);
            $traverser->addVisitor($visitor);
            $pluginVisitors[] = $visitor;
        }

        $traverser->traverse($stmts);

        $symbols = $symbolVisitor->symbols();

        if (count($symbols) === 0) {
            $symbols = [
                (new PseudoFileExtractor($relPath, $contents))->extract(),
            ];
        }

        $chunkExtractor = new ChunkExtractor($contents, $relPath);
        $chunks = $chunkExtractor->extract($symbols);

        $edges = array_merge(
            $inheritanceVisitor->edges(),
            $callVisitor->edges(),
        );

        foreach ($pluginVisitors as $visitor) {
            $edges = array_merge($edges, $visitor->edges());
        }

        $this->graph->recordFile(
            $relPath,
            $sha256,
            $size,
            $mtime,
            $symbols,
            $edges,
            $chunks,
        );

        $stats->addSymbols(count($symbols));
        $stats->addEdges(count($edges));
        $stats->addChunks(count($chunks));
    }
}
