<h2>Create Repository</h2>

<form method="post" action="/repos">
    <div>
        <label>Name</label>
        <input type="text" name="name" required>
    </div>
    <div>
        <label>Description</label>
        <textarea name="description"></textarea>
    </div>
    <button type="submit">Create</button>
</form>
