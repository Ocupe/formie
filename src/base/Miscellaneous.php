<?php
namespace verbb\formie\base;

use verbb\formie\elements\Submission;
use verbb\formie\events\ModifyMiscellaneousPayloadEvent;

use Craft;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;

use yii\helpers\Markdown;

abstract class Miscellaneous extends Integration
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_MISCELLANEOUS_PAYLOAD = 'modifyMiscellaneousPayload';


    // Static Methods
    // =========================================================================

    public static function typeName(): string
    {
        return Craft::t('formie', 'Miscellaneous');
    }


    // Public Methods
    // =========================================================================

    public function getIconUrl(): string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getAssetManager()->getPublishedUrl("@verbb/formie/web/assets/cp/dist/img/miscellaneous/{$handle}.svg", true);
    }

    /**
     * @inheritDoc
     */
    public function getSettingsHtml(): ?string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        $handle = StringHelper::toKebabCase(static::displayName());

        return Craft::$app->getView()->renderTemplate("formie/integrations/miscellaneous/{$handle}/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('formie/settings/miscellaneous/edit/' . $this->id);
    }

    protected function generatePayloadValues(Submission $submission): array
    {
        $submissionContent = $submission->getValuesAsJson();
        $formAttributes = Json::decode(Json::encode($submission->getForm()->getAttributes()));

        $submissionAttributes = $submission->toArray([
            'id',
            'formId',
            'status',
            'userId',
            'ipAddress',
            'isIncomplete',
            'isSpam',
            'spamReason',
            'title',
            'dateCreated',
            'dateUpdated',
            'dateDeleted',
            'trashed',
        ]);

        // Trim the form settings a little
        unset($formAttributes['settings']['integrations']);

        $payload = [
            'json' => [
                'submission' => array_merge($submissionAttributes, $submissionContent),
                'form' => $formAttributes,
            ],
        ];

        // Fire a 'modifyMiscellaneousPayload' event
        $event = new ModifyMiscellaneousPayloadEvent([
            'submission' => $submission,
            'payload' => $payload,
        ]);
        $this->trigger(self::EVENT_MODIFY_MISCELLANEOUS_PAYLOAD, $event);

        return $event->payload;
    }
}
