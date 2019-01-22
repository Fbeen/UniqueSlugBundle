<?php

namespace Fbeen\UniqueSlugBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Sensio\Bundle\GeneratorBundle\Command\AutoComplete\EntitiesAutoCompleter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Fbeen\UniqueSlugBundle\Listener\SlugUpdater;


class SlugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('fbeen:generate:slugs')
            ->setDescription('Write new slugs for every record of an entity')
            ->addArgument(
                'entity',
                InputArgument::OPTIONAL,
                'Who do you want to greet?'
            )
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager($input->getOption('em'));
        
        $helper = $this->getHelper('question');
        
        $output->writeln("\n<question>                                      ");
        $output->writeln(' Welcome to the Fbeen slug generator. ');
        $output->writeln("                                      </question>\n");
        
        $entityName = $input->getArgument('entity');
        
        if(!$entityName)
        {
            $output->writeln('This command helps you to update slugs on an entire table.');
            $output->writeln('First, you need to give the entity for which you want to generate the slugs.');
            $output->writeln("\nYou must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>.\n");
            
            $question = new Question('<info>The Entity shortcut name: </info>');

            $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'));
            $autocompleter = new EntitiesAutoCompleter($em);
            $autocompleteEntities = $autocompleter->getSuggestions();
            $question->setAutocompleterValues($autocompleteEntities);
            $entityName = $helper->ask($input, $output, $question);

        }
        
        $entities = $em->getRepository($entityName)->findAll();
        
        $question = new ConfirmationQuestion('<info>Continue generating slugs for ' . $entityName . '</info> [<comment>no</comment>]? ', false);

        if (!$helper->ask($input, $output, $question)) {
            return;
        }

        $output->writeln("\nGenerating the slugs...");
        
        $slugupdater = new SlugUpdater();

        foreach($entities as $entity)
        {            blog:
                driver:   pdo_mysql
                options:
            $slugupdater->preUpdate(new LifecycleEventArgs($entity, $em));
            $em->flush();
        }
        
        $output->writeln("\nDone!\n");
    }
}
