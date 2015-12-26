<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Generate\EntityContentCommand.
 */

namespace Drupal\Console\Command\Generate;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Generate\EntityCommand;
use Drupal\Console\Generator\EntityContentGenerator;
use Drupal\Console\Style\DrupalStyle;

class EntityContentCommand extends EntityCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setEntityType('EntityContent');
        $this->setCommandName('generate:entity:content');
        parent::configure();

        $this->addOption(
            'has-bundles',
            null,
            InputOption::VALUE_NONE,
            $this->trans('commands.generate.entity.content.options.has-bundles')
        );
        $this->addOption(
            'fields',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Description',
            []
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        $output = new DrupalStyle($input, $output);
        $stringUtils = $this->getStringHelper();
        // --bundle-of option
        $bundle_of = $input->getOption('has-bundles');
        if (!$bundle_of) {
            $bundle_of = $output->confirm(
                $this->trans('commands.generate.entity.content.questions.has-bundles'),
                false
            );
            $input->setOption('has-bundles', $bundle_of);
        }

        $fields = $input->getOption('fields');

        $define_more_fields = $output->confirm(
            'Desea definir más campos?',
            false
        );
        if ($define_more_fields) {
            $types = [
                'string',
                'integer',
            ];
            while(true) {
                $field_type = $output->choiceNoList(
                    'Que tipo de campo',
                    $types,
                    null,
                    true
                );
                if (empty($field_type)) {
                    break;
                }

                while (true) {
                    $field_name = $output->ask(
                        'Machine Name',
                        '',
                        function ($name) use ($output) {
                          if ($name != '') {

                            return $name;
                          } else {
                            $io->error('Debe ingresar un nombre de maquina valido');

                            return false;
                          }
                        }
                    );

                    if ($field_name) {
                        break;
                    }

                    $field_name = $stringUtils->createMachineName($field_name);
                }



                $field_label = $output->ask(
                    'Label?',
                    $field_name
                );

                $fields[] = [
                    'type' => $field_type,
                    'name' => $field_name,
                    'label' => $field_label,
                ];

                $output->info('Next field');
            }

            $input->setOption('fields', $fields);
        }


    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getOption('module');
        $entity_class = $input->getOption('entity-class');
        $entity_name = $input->getOption('entity-name');
        $label = $input->getOption('label');
        $has_bundles = $input->getOption('has-bundles');

        $bundle_entity_name = $has_bundles ? $entity_name . '_type' : null;

        $fields = $input->getOption('fields');

        $this
            ->getGenerator()
            ->generate($module, $entity_name, $entity_class, $label, $bundle_entity_name, $fields);

        if ($has_bundles) {
            $this->getChain()->addCommand(
                'generate:entity:config', [
                '--module' => $module,
                '--entity-class' => $entity_class . 'Type',
                '--entity-name' => $entity_name . '_type',
                '--label' => $label . ' type',
                '--bundle-of' => $entity_name
                ]
            );
        }


    }

    protected function createGenerator()
    {
        return new EntityContentGenerator();
    }
}
