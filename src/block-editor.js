import { MediaUpload } from '@wordpress/media-utils';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { Button } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';

const DefaultFeaturedImagePanel = () => {
    const defaultImageId = useSelect(select => 
        select('core').getEntityRecord('root', 'site')?.ssfi_default_image
    );

    const { saveEntityRecord } = useDispatch('core');

    const defaultImage = useSelect(select =>
        defaultImageId ? select('core').getMedia(defaultImageId) : null
    );

    const updateDefaultImage = (image) => {
        saveEntityRecord('root', 'site', {
            ssfi_default_image: image ? image.id : 0
        });
    };

    return (
        <PluginDocumentSettingPanel
            name="ssfi-default-image-panel"
            title={__('Default Featured Image', 'super-swank-featured-image')}
            className="ssfi-default-image-panel"
        >
            <div className="ssfi-image-preview">
                {defaultImage && (
                    <img
                        src={defaultImage.source_url}
                        alt=""
                        style={{ maxWidth: '100%' }}
                    />
                )}
            </div>
            <MediaUpload
                onSelect={updateDefaultImage}
                allowedTypes={['image']}
                value={defaultImageId}
                render={({ open }) => (
                    <Button
                        onClick={open}
                        variant="secondary"
                        className="ssfi-select-image"
                    >
                        {defaultImageId
                            ? __('Change Image', 'super-swank-featured-image')
                            : __('Select Image', 'super-swank-featured-image')}
                    </Button>
                )}
            />
            {defaultImageId > 0 && (
                <Button
                    onClick={() => updateDefaultImage(null)}
                    variant="link"
                    className="ssfi-remove-image"
                    isDestructive
                >
                    {__('Remove Image', 'super-swank-featured-image')}
                </Button>
            )}
        </PluginDocumentSettingPanel>
    );
};

registerPlugin('ssfi-default-image', {
    render: DefaultFeaturedImagePanel,
    icon: 'format-image'
}); 