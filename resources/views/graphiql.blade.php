<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>GraphiQL</title>
    <link rel="stylesheet" href="https://unpkg.com/graphiql/graphiql.min.css" />
</head>
<body style="margin: 0;">
<div id="graphiql" style="height: 100vh;"></div>

<script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://unpkg.com/graphiql/graphiql.min.js"></script>
<script>
    const graphQLFetcher = graphQLParams =>
        fetch('/graphql', {
            method: 'post',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(graphQLParams),
            credentials: 'include', // <-- если используешь sanctum
        }).then(response => response.json());

    ReactDOM.render(
        React.createElement(GraphiQL, { fetcher: graphQLFetcher }),
        document.getElementById('graphiql'),
    );
</script>
</body>
</html>
