<?php

/**
 * This file is part of the fbeen\uniqueslugbundle
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 * 
 */

namespace Fbeen\UniqueSlugBundle\Maker;

use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\MakerBundle\Util\ClassSourceManipulator;
use Symfony\Bundle\MakerBundle\Util\ClassDetails;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity; 
use Doctrine\ORM\EntityManager;
use Fbeen\UniqueSlugBundle\Annotation\Slug;
use Fbeen\UniqueSlugBundle\Custom\SlugUpdater;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

final class MakeSlug extends AbstractMaker
{
    private $fileManager;
    private $entityManager;
    private $doctrineHelper;
    private $slugUpdater;
    private $command;

    public function __construct(FileManager $fileManager, EntityManager $entityManager, DoctrineHelper $doctrineHelper, SlugUpdater $slugUpdater)
    {
        $this->fileManager = $fileManager;
        $this->entityManager = $entityManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->slugUpdater = $slugUpdater;
    }

    public static function getCommandName(): string
    {
        return 'make:slug';
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->setDescription('Creates slugs for Doctrine entity class')
            ->addArgument('entity-class', InputArgument::OPTIONAL, 'The class name of the entity where you want to create slugs for (e.g. <fg=yellow>Book</>)')
            ->addArgument('slug-property', InputArgument::OPTIONAL, 'The name of the property or method to generate a slug from (e.g. <fg=yellow>title</>)')
            ->addOption('regenerate', null, InputOption::VALUE_NONE, 'Regenerate all the existing slugs in the database-table of the given entity.')
            ->setHelp(file_get_contents(__DIR__.'/../Resources/help/MakeSlug.txt'))
        ;
        
        $this->command = $command;
        
        $inputConfig->setArgumentAsNonInteractive('entity-class');
        $inputConfig->setArgumentAsNonInteractive('slug-property');
    }
    
    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }
    }
    
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete()),
            'Entity\\'
        );

        $entityDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($entityClassDetails->getFullName());
        
        if($input->getOption('regenerate')) {
            $this->regenerateSlugs($entityClassDetails, $io);
            return;
        }
        
        $fieldsArray = $entityDoctrineDetails->getDisplayFields();

        if(isset($fieldsArray['slug']))
        {
            $io->text('<fg=red>fieldname "slug" already exists!</>');
            if('string' != $fieldsArray['slug']['type']) {
                $io->text('<fg=red>Slug must be of type String!</>');
            }
            return;
        }
        
        if (null === $input->getArgument('slug-property')) {
            $argument = $this->command->getDefinition()->getArgument('slug-property');

            $fieldsNames = \array_keys($fieldsArray);

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($fieldsNames);

            $value = $io->askQuestion($question);

            $input->setArgument('slug-property', $value);
        }
        
        if(!isset($fieldsArray[$input->getArgument('slug-property')]) && !$this->publicMethodExists($entityClassDetails->getFullName(), $input->getArgument('slug-property'))) {
            throw new RuntimeCommandException(sprintf('There is no property or method "%s" in the entity "%s".', $input->getArgument('slug-property'), $input->getArgument('entity-class')));
        }

        $newField = array(
            'fieldName' => 'slug',
            'type' => 'string',
            'length' => 255,
            'unique' => true
        );

        $entityPath = $this->getPathOfClass($entityClassDetails->getFullName());

        $manipulator = $this->createClassManipulator($entityPath, $io, false);

        $manipulator->addUseStatementIfNecessary(UniqueEntity::class);
        $manipulator->addUseStatementIfNecessary(Slug::class);

        $annotationOptions = $newField;
        unset($annotationOptions['fieldName']);
        $manipulator->addEntityField($newField['fieldName'], $annotationOptions, ['@Slug("' . $input->getArgument('slug-property') . '")']);

        $this->fileManager->dumpFile($entityPath, $manipulator->getSourceCode());
               
        $io->text([
            'Property <fg=green>slug</> with necessary annotations created inside the <fg=yellow>' . $entityClassDetails->getShortName() . '</> entity.',
            '',
            'Next: When you\'re ready, create a migration with <comment>make:migration</comment>',
            'Last: When your database schema is up to date you could (re)generate all slugs in the table by <comment>make:slug ' . $input->getArgument('entity-class') . ' --regenerate</comment>',
            '',
        ]);
    }
    
    /**
     * regenerates all the slugs in a entity table
     * 
     * @param Symfony\Bundle\MakerBundle\Util\ClassNameDetails  $entityClassDetails  An object with some entity data
     * @param Symfony\Bundle\MakerBundle\ConsoleStyle           $io                  A wrapper to write output to the console
     */
    public function regenerateSlugs(ClassNameDetails $entityClassDetails, ConsoleStyle $io) : void
    {
        $entityName = $entityClassDetails->getFullName();

        $entities = $this->entityManager->getRepository($entityName)->findAll();

        $io->text('<fg=green>Generating the slugs...</>');

        foreach($entities as $entity)
        {
            $this->slugUpdater->updateSlugs($entity);
            $this->entityManager->flush();
        }

        $io->text('<fg=blue>Done!</> ' . count($entities) . ' slugs written.');
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(
            Route::class,
            'router'
        );
        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );
        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );
        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );
        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm-pack'
        );
        $dependencies->addClassDependency(
            CsrfTokenManager::class,
            'security-csrf'
        );
        $dependencies->addClassDependency(
            ParamConverter::class,
            'annotations'
        );
    }
    
    private function createClassManipulator(string $path, ConsoleStyle $io, bool $overwrite): ClassSourceManipulator
    {
        $manipulator = new ClassSourceManipulator($this->fileManager->getFileContents($path), $overwrite);
        $manipulator->setIo($io);

        return $manipulator;
    }
    
    private function getPathOfClass(string $class): string
    {
        $classDetails = new ClassDetails($class);

        return $classDetails->getPath();
    }
    
    /**
     * tests of a given method exists and have public access for a given class
     * 
     * @param object|string  $class   An object instance or a class name
     * @param string         $method  A method name
     * 
     * @return bool TRUE if the method exists and have public access, otherwise FALSE
     */
    private function publicMethodExists($class, string $method): bool
    {
        if(method_exists($class, $method))
        {
            $reflection = new \ReflectionMethod($class, $method);
            if($reflection->isPublic()) {
                return true;
            }
        }

        return false;
    }
}
