/**
 * WordPress dependencies
 */
import { Component, Fragment } from "@wordpress/element";
import { Spinner, __experimentalText as Text } from "@wordpress/components";

class RepeatWeekly extends Component {
    constructor(props) {
        super(props);
        this.state = {
            list: [],
            loading: true
        };
    }

    componentDidMount() {
        this.runApiFetch();
    }

    runApiFetch() {
        const { getCurrentPostId } = wp.data.select("core/editor");
        const postId = getCurrentPostId();
        wp.apiFetch({
            path: `/rrze-newsletter/v1/repeat/weekly/${postId}`
        }).then(data => {
            this.setState({
                list: data,
                loading: false
            });
        });
    }

    render() {
        return (
            <Fragment>
                {this.state.loading ? (
                    <Spinner />
                ) : (
                    <Text>{JSON.parse(this.state.list)}</Text>
                )}
            </Fragment>
        );
    }
}

export default RepeatWeekly;
